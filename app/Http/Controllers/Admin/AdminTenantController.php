<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminTenantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenants = Tenant::with(['plan', 'activeSubscription.plan', 'users', 'invoices', 'customers'])
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->plan, fn ($q) => $q->whereHas('plan', fn ($p) => $p->where('slug', $request->plan)))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $tenants->each(function ($tenant) {
            $tenant->invoices_count  = $tenant->invoices->count();
            $tenant->customers_count = $tenant->customers->count();
            $tenant->users_count     = $tenant->users->count();
            unset($tenant->invoices, $tenant->customers);
        });

        return response()->json([
            'tenants' => $tenants,
            'plans'   => Plan::orderBy('sort_order')->get(['id', 'name', 'slug']),
            'filters' => $request->only(['search', 'plan']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'company_name'   => 'required|string|max:255',
            'owner_name'     => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|string|min:8',
            'gstin'          => ['nullable', 'string', 'size:15'],
            'state'          => 'nullable|string|max:100',
            'invoice_prefix' => 'nullable|string|max:10',
            'plan_id'        => 'nullable|exists:plans,id',
        ]);

        DB::transaction(function () use ($request) {
            $tenant = Tenant::create([
                'name'              => $request->company_name,
                'gstin'             => $request->gstin ? strtoupper($request->gstin) : null,
                'state'             => $request->state,
                'invoice_prefix'    => strtoupper($request->invoice_prefix ?? 'INV'),
                'subscription_plan' => 'free',
                'plan_id'           => $request->plan_id,
            ]);

            User::create([
                'tenant_id' => $tenant->id,
                'name'      => $request->owner_name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'role'      => 'owner',
            ]);

            if ($request->plan_id) {
                $tenant->subscriptions()->create([
                    'plan_id'   => $request->plan_id,
                    'status'    => 'active',
                    'starts_at' => now(),
                    'ends_at'   => now()->addMonth(),
                ]);
            }
        });

        return response()->json(['message' => "Tenant '{$request->company_name}' created successfully."], 201);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load(['plan', 'users', 'subscriptions.plan']);

        return response()->json([
            'tenant' => $tenant,
            'plans'  => Plan::orderBy('sort_order')->get(),
            'stats'  => [
                'total_revenue'    => $tenant->payments()->sum('amount'),
                'invoice_count'    => $tenant->invoices()->count(),
                'customer_count'   => $tenant->customers()->count(),
                'monthly_invoices' => $tenant->monthly_invoice_count,
            ],
        ]);
    }

    public function assignPlan(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate(['plan_id' => 'required|exists:plans,id']);

        $plan = Plan::findOrFail($request->plan_id);
        $tenant->update(['plan_id' => $plan->id]);

        $tenant->subscriptions()->create([
            'plan_id'   => $plan->id,
            'status'    => 'active',
            'starts_at' => now(),
            'ends_at'   => now()->addMonth(),
        ]);

        return response()->json(['message' => "Plan changed to {$plan->name}."]);
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $ownerUser = $tenant->users()->where('role', 'owner')->first();

        $request->validate([
            'company_name'   => 'required|string|max:255',
            'owner_name'     => 'required|string|max:255',
            'email'          => [
                'required', 'email',
                $ownerUser
                    ? Rule::unique('users', 'email')->where(fn ($q) => $q->where('id', '!=', $ownerUser->id))
                    : Rule::unique('users', 'email'),
            ],
            'gstin'          => ['nullable', 'string', 'size:15'],
            'state'          => 'nullable|string|max:100',
            'invoice_prefix' => 'nullable|string|max:10',
            'new_password'   => 'nullable|string|min:8',
        ]);

        $tenant->update([
            'name'           => $request->company_name,
            'gstin'          => $request->gstin ? strtoupper($request->gstin) : null,
            'state'          => $request->state,
            'invoice_prefix' => strtoupper($request->invoice_prefix ?? 'INV'),
        ]);

        if ($ownerUser) {
            $ownerData = ['name' => $request->owner_name, 'email' => $request->email];
            if ($request->filled('new_password')) {
                $ownerData['password'] = Hash::make($request->new_password);
            }
            $ownerUser->update($ownerData);
        }

        return response()->json(['message' => "Tenant '{$tenant->name}' updated successfully."]);
    }

    public function toggleSuspend(Tenant $tenant): JsonResponse
    {
        $tenant->update(['is_suspended' => ! $tenant->is_suspended]);
        $status = $tenant->is_suspended ? 'suspended' : 'reactivated';
        return response()->json(['message' => "Tenant {$status} successfully."]);
    }

    public function destroy(Tenant $tenant): Response
    {
        $tenant->delete();
        return response()->noContent();
    }
}
