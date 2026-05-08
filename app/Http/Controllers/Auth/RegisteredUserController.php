<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            // Account
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // Business
            'company_name' => 'required|string|max:255',
            'gstin' => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'state' => 'nullable|string|max:100',
            'state_code' => 'nullable|string|max:2',
            'invoice_prefix' => 'nullable|string|max:10',
        ]);

        $user = DB::transaction(function () use ($request) {
            // Create the tenant (business account)
            $tenant = Tenant::create([
                'name' => $request->company_name,
                'gstin' => $request->gstin ? strtoupper($request->gstin) : null,
                'state' => $request->state,
                'state_code' => $request->state_code,
                'invoice_prefix' => strtoupper($request->invoice_prefix ?? 'INV'),
                'subscription_plan' => 'free',
            ]);

            // Create the owner user linked to this tenant
            return User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'owner',
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
