<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Each entry defines a setting record.
     * `encrypted` marks secrets that are stored with Laravel's Crypt.
     * `env_key` is the .env key to read an initial value from (can be left empty).
     */
    private array $definitions = [
        // ── Mail ──────────────────────────────────────────────────────────────
        ['key' => 'MAIL_MAILER',       'group' => 'mail', 'label' => 'Mail Driver',       'encrypted' => false, 'description' => 'Mail driver (smtp, log, resend …)'],
        ['key' => 'MAIL_HOST',         'group' => 'mail', 'label' => 'SMTP Host',          'encrypted' => false, 'description' => 'SMTP server hostname'],
        ['key' => 'MAIL_PORT',         'group' => 'mail', 'label' => 'SMTP Port',          'encrypted' => false, 'description' => 'SMTP server port (587 for TLS)'],
        ['key' => 'MAIL_USERNAME',     'group' => 'mail', 'label' => 'SMTP Username',      'encrypted' => false, 'description' => 'SMTP authentication username / email'],
        ['key' => 'MAIL_PASSWORD',     'group' => 'mail', 'label' => 'SMTP Password',      'encrypted' => true,  'description' => 'SMTP app password (stored encrypted)'],
        ['key' => 'MAIL_ENCRYPTION',   'group' => 'mail', 'label' => 'SMTP Encryption',    'encrypted' => false, 'description' => 'Encryption protocol (tls or ssl)'],
        ['key' => 'MAIL_FROM_ADDRESS', 'group' => 'mail', 'label' => 'From Address',       'encrypted' => false, 'description' => 'Default sender email address'],
        ['key' => 'MAIL_FROM_NAME',    'group' => 'mail', 'label' => 'From Name',          'encrypted' => false, 'description' => 'Default sender display name'],

        // ── Razorpay ──────────────────────────────────────────────────────────
        ['key' => 'RAZORPAY_KEY',    'group' => 'razorpay', 'label' => 'Razorpay Key ID', 'encrypted' => false, 'description' => 'Razorpay API key (rzp_live_… or rzp_test_…)'],
        ['key' => 'RAZORPAY_SECRET', 'group' => 'razorpay', 'label' => 'Razorpay Secret', 'encrypted' => true,  'description' => 'Razorpay API secret (stored encrypted)'],

        // ── WhatsApp ──────────────────────────────────────────────────────────
        ['key' => 'WHATSAPP_TOKEN',           'group' => 'whatsapp', 'label' => 'WhatsApp Access Token',  'encrypted' => true,  'description' => 'Meta/WhatsApp Business API access token'],
        ['key' => 'WHATSAPP_PHONE_NUMBER_ID', 'group' => 'whatsapp', 'label' => 'WhatsApp Phone Number ID', 'encrypted' => false, 'description' => 'WhatsApp Business phone number ID'],

        // ── Resend ────────────────────────────────────────────────────────────
        ['key' => 'RESEND_API_KEY', 'group' => 'resend', 'label' => 'Resend API Key', 'encrypted' => true, 'description' => 'Resend.com API key for transactional email'],

        // ── AWS / S3 ──────────────────────────────────────────────────────────
        ['key' => 'AWS_ACCESS_KEY_ID',     'group' => 'aws', 'label' => 'AWS Access Key ID',     'encrypted' => true,  'description' => 'AWS IAM access key ID'],
        ['key' => 'AWS_SECRET_ACCESS_KEY', 'group' => 'aws', 'label' => 'AWS Secret Access Key', 'encrypted' => true,  'description' => 'AWS IAM secret access key'],
        ['key' => 'AWS_DEFAULT_REGION',    'group' => 'aws', 'label' => 'AWS Region',            'encrypted' => false, 'description' => 'AWS region (e.g. ap-south-1)'],
        ['key' => 'AWS_BUCKET',            'group' => 'aws', 'label' => 'S3 Bucket Name',        'encrypted' => false, 'description' => 'S3 bucket for file storage'],

        // ── App ───────────────────────────────────────────────────────────────
        ['key' => 'APP_NAME',      'group' => 'app', 'label' => 'Application Name', 'encrypted' => false, 'description' => 'Application display name shown in UI and emails'],
        ['key' => 'BCRYPT_ROUNDS', 'group' => 'app', 'label' => 'Bcrypt Rounds',    'encrypted' => false, 'description' => 'Password hashing cost factor (10–12 recommended)'],
    ];

    public function run(): void
    {
        foreach ($this->definitions as $def) {
            $exists = SystemSetting::where('key', $def['key'])->first();

            if ($exists) {
                // Only refresh metadata — never overwrite a value already managed in the DB
                $exists->update([
                    'group'       => $def['group'],
                    'label'       => $def['label'],
                    'description' => $def['description'],
                    'encrypted'   => $def['encrypted'],
                ]);
                continue;
            }

            // Read initial value from the current environment
            $envValue = env($def['key'], '');

            $rawValue = '';
            if ($envValue !== '') {
                $rawValue = $def['encrypted']
                    ? Crypt::encryptString((string) $envValue)
                    : (string) $envValue;
            }

            SystemSetting::create([
                'key'         => $def['key'],
                'raw_value'   => $rawValue,
                'encrypted'   => $def['encrypted'],
                'group'       => $def['group'],
                'label'       => $def['label'],
                'description' => $def['description'],
            ]);
        }

        $this->command->info('SystemSettings seeded — ' . count($this->definitions) . ' settings registered.');
    }
}
