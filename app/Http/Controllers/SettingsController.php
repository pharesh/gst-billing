<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Settings/Index', [
            'tenant' => $request->user()->tenant,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'gstin'          => 'nullable|string|max:15',
            'address'        => 'nullable|string|max:500',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:100',
            'state_code'     => 'nullable|string|max:3',
            'pincode'        => 'nullable|string|max:10',
            'phone'          => 'nullable|string|max:15',
            'email'          => 'nullable|email|max:255',
            'invoice_prefix' => 'required|string|max:10|alpha_num',
            'bank_details'   => 'nullable|array',
            'bank_details.bank_name'      => 'nullable|string|max:100',
            'bank_details.account_number' => 'nullable|string|max:30',
            'bank_details.ifsc_code'      => 'nullable|string|max:20',
            'bank_details.account_name'   => 'nullable|string|max:100',
        ]);

        $request->user()->tenant->update($validated);

        return back()->with('success', 'Business settings updated.');
    }
}
