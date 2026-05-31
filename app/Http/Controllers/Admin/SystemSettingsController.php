<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemSettingsController extends Controller
{
    /**
     * List all settings grouped, masking encrypted values.
     * Encrypted values show null; `is_set` indicates whether a value exists.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'settings' => SettingsService::allByGroup(),
        ]);
    }

    /**
     * Update a single setting by key.
     * The key is the setting's ENV-style name (e.g. MAIL_PASSWORD).
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $request->validate([
            'value' => 'required|string|max:2000',
        ]);

        $setting = SystemSetting::where('key', $key)->first();
        if (!$setting) {
            return response()->json(['message' => 'Setting not found.'], 404);
        }

        SettingsService::set($key, $request->string('value'));

        return response()->json(['message' => 'Setting updated successfully.']);
    }

    /**
     * Update multiple settings at once.
     * Body: { "settings": [{ "key": "MAIL_HOST", "value": "smtp.gmail.com" }, ...] }
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'settings'          => 'required|array|min:1',
            'settings.*.key'    => 'required|string',
            'settings.*.value'  => 'required|string|max:2000',
        ]);

        $notFound = [];
        foreach ($request->input('settings') as $item) {
            $updated = SettingsService::set($item['key'], $item['value']);
            if (!$updated) {
                $notFound[] = $item['key'];
            }
        }

        SettingsService::clearCache();

        if (!empty($notFound)) {
            return response()->json([
                'message'   => 'Some keys were not found and skipped.',
                'not_found' => $notFound,
            ], 207);
        }

        return response()->json(['message' => 'Settings updated successfully.']);
    }
}
