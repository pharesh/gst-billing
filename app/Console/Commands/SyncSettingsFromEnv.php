<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class SyncSettingsFromEnv extends Command
{
    protected $signature   = 'settings:sync-env {--force : Overwrite values already set in the database}';
    protected $description = 'Sync service credentials from .env into the system_settings MongoDB collection';

    public function handle(): int
    {
        $force    = $this->option('force');
        $settings = SystemSetting::all()->keyBy('key');

        if ($settings->isEmpty()) {
            $this->error('No settings found. Run: php artisan db:seed --class=SystemSettingsSeeder first.');
            return self::FAILURE;
        }

        $updated = 0;
        $skipped = 0;

        foreach ($settings as $key => $setting) {
            $envValue = env($key);

            if ($envValue === null || $envValue === '') {
                $this->line("  <fg=gray>SKIP</> {$key} (not in .env)");
                $skipped++;
                continue;
            }

            if (!$force && $setting->raw_value !== null && $setting->raw_value !== '') {
                $this->line("  <fg=yellow>SKIP</> {$key} (already set in DB — use --force to overwrite)");
                $skipped++;
                continue;
            }

            $raw = $setting->encrypted
                ? Crypt::encryptString((string) $envValue)
                : (string) $envValue;

            $setting->update(['raw_value' => $raw]);
            $this->line("  <fg=green>SET</>  {$key}" . ($setting->encrypted ? ' (encrypted)' : ''));
            $updated++;
        }

        $this->newLine();
        $this->info("Done. {$updated} updated, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
