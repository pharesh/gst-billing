<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingsService
{
    private const CACHE_TTL    = 3600;
    private const CACHE_PREFIX = 'sys_setting:';

    // Keys that require integer casting when applied to config
    private const INTEGER_KEYS = ['MAIL_PORT', 'BCRYPT_ROUNDS'];

    /**
     * Retrieve a single setting value, decrypting if needed.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::remember(self::CACHE_PREFIX . $key, self::CACHE_TTL, function () use ($key) {
            $setting = SystemSetting::where('key', $key)->first();
            if (!$setting || $setting->raw_value === null || $setting->raw_value === '') {
                return null;
            }
            return $setting->encrypted
                ? Crypt::decryptString($setting->raw_value)
                : $setting->raw_value;
        });

        return $value ?? $default;
    }

    /**
     * Persist a setting value, encrypting if the record is marked encrypted.
     */
    public static function set(string $key, string $value): bool
    {
        $setting = SystemSetting::where('key', $key)->first();
        if (!$setting) {
            return false;
        }

        $raw = $setting->encrypted ? Crypt::encryptString($value) : $value;
        $setting->update(['raw_value' => $raw]);

        Cache::forget(self::CACHE_PREFIX . $key);
        Cache::forget('sys_settings_all');

        return true;
    }

    /**
     * Return all settings grouped, masking encrypted values.
     */
    public static function allByGroup(): array
    {
        return Cache::remember('sys_settings_all', self::CACHE_TTL, function () {
            $grouped = [];
            SystemSetting::orderBy('group')->orderBy('key')->each(function ($setting) use (&$grouped) {
                $grouped[$setting->group][] = [
                    'key'         => $setting->key,
                    'label'       => $setting->label,
                    'description' => $setting->description,
                    'group'       => $setting->group,
                    'encrypted'   => (bool) $setting->encrypted,
                    'is_set'      => $setting->raw_value !== null && $setting->raw_value !== '',
                    'value'       => $setting->encrypted ? null : $setting->raw_value,
                ];
            });
            return $grouped;
        });
    }

    /**
     * Flush cache for a specific key or everything.
     */
    public static function clearCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        } else {
            Cache::forget('sys_settings_all');
            SystemSetting::pluck('key')->each(fn ($k) => Cache::forget(self::CACHE_PREFIX . $k));
        }
    }

    /**
     * Load all DB settings into Laravel's runtime config.
     * Called from AppServiceProvider::boot() — wrapped in try/catch
     * so a DB outage degrades gracefully to .env fallbacks.
     */
    public static function loadIntoConfig(): void
    {
        try {
            $map      = self::configMap();
            $settings = SystemSetting::whereIn('key', array_keys($map))->get()->keyBy('key');

            foreach ($settings as $settingKey => $setting) {
                if (empty($setting->raw_value)) {
                    continue;
                }

                $value = $setting->encrypted
                    ? Crypt::decryptString($setting->raw_value)
                    : $setting->raw_value;

                if (in_array($settingKey, self::INTEGER_KEYS, true)) {
                    $value = (int) $value;
                }

                foreach ((array) $map[$settingKey] as $configKey) {
                    config([$configKey => $value]);
                }
            }
        } catch (\Throwable) {
            // DB unavailable — env-based config values remain in effect
        }
    }

    /**
     * Maps setting keys to one or more Laravel config paths.
     */
    public static function configMap(): array
    {
        return [
            'MAIL_MAILER'              => 'mail.default',
            'MAIL_HOST'                => 'mail.mailers.smtp.host',
            'MAIL_PORT'                => 'mail.mailers.smtp.port',
            'MAIL_USERNAME'            => 'mail.mailers.smtp.username',
            'MAIL_PASSWORD'            => 'mail.mailers.smtp.password',
            'MAIL_ENCRYPTION'          => 'mail.mailers.smtp.encryption',
            'MAIL_FROM_ADDRESS'        => 'mail.from.address',
            'MAIL_FROM_NAME'           => 'mail.from.name',
            'RAZORPAY_KEY'             => 'services.razorpay.key',
            'RAZORPAY_SECRET'          => 'services.razorpay.secret',
            'WHATSAPP_TOKEN'           => 'services.whatsapp.token',
            'WHATSAPP_PHONE_NUMBER_ID' => 'services.whatsapp.phone_number_id',
            'RESEND_API_KEY'           => 'services.resend.key',
            'AWS_ACCESS_KEY_ID'        => ['services.ses.key', 'filesystems.disks.s3.key'],
            'AWS_SECRET_ACCESS_KEY'    => ['services.ses.secret', 'filesystems.disks.s3.secret'],
            'AWS_DEFAULT_REGION'       => ['services.ses.region', 'filesystems.disks.s3.region'],
            'AWS_BUCKET'               => 'filesystems.disks.s3.bucket',
            'APP_NAME'                 => 'app.name',
            'BCRYPT_ROUNDS'            => 'hashing.bcrypt.rounds',
        ];
    }
}
