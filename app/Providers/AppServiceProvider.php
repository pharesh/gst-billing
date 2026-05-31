<?php

namespace App\Providers;

use App\Services\SettingsService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Vite::prefetch(concurrency: 3);

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Override config with values stored in MongoDB.
        // Falls back silently to .env values if DB is unreachable.
        SettingsService::loadIntoConfig();
    }
}
