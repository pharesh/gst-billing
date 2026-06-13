<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Services\SettingsService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Vite::prefetch(concurrency: 3);

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Override config with values stored in MongoDB.
        // Falls back silently to .env values if DB is unreachable.
        SettingsService::loadIntoConfig();
    }
}
