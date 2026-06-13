<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);

        $middleware->alias([
            'tenant'      => \App\Http\Middleware\EnsureTenantLinked::class,
            'superadmin'  => \App\Http\Middleware\EnsureSuperAdmin::class,
            'plan.limit'  => \App\Http\Middleware\CheckPlanLimits::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\MongoDB\Driver\Exception\AuthenticationException $e, $request) {
            return response()->json(['message' => 'Database authentication error. Check server configuration.'], 503);
        });
        $exceptions->render(function (\MongoDB\Driver\Exception\ConnectionTimeoutException $e, $request) {
            return response()->json(['message' => 'Database connection timeout. Please try again.'], 503);
        });
        $exceptions->render(function (\MongoDB\Driver\Exception\RuntimeException $e, $request) {
            return response()->json(['message' => 'Database error. Please try again.'], 503);
        });
    })->create();
