<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status'  => 'ok',
        'app'     => config('app.name'),
        'api'     => url('/api'),
        'message' => 'GST Billing API is running. Use /api/* endpoints.',
    ]);
});
