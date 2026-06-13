<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password'       => ['required', 'confirmed', Rules\Password::defaults()],
            'company_name'   => 'required|string|max:255',
            'gstin'          => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'state'          => 'nullable|string|max:100',
            'state_code'     => 'nullable|string|max:2',
            'invoice_prefix' => 'nullable|string|max:10',
        ]);

        $user = DB::transaction(function () use ($request) {
            $tenant = Tenant::create([
                'name'              => $request->company_name,
                'gstin'             => $request->gstin ? strtoupper($request->gstin) : null,
                'state'             => $request->state,
                'state_code'        => $request->state_code,
                'invoice_prefix'    => strtoupper($request->invoice_prefix ?? 'INV'),
                'subscription_plan' => 'free',
            ]);

            return User::create([
                'tenant_id'    => $tenant->id,
                'name'         => $request->name,
                'email'        => $request->email,
                'password'     => Hash::make($request->password),
                'role'         => 'owner',
                'otp_verified' => true,
            ]);
        });

        event(new Registered($user));

        $token = $user->createToken('web')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user], 201);
    }
}
