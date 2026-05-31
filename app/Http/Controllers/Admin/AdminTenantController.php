<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminTenantController extends Controller
{
    public function index(Request $request)
    {
        // withCount() is not supported by MongoDB; load relationships and count in PHP
        $tenants = Tenant::with(['plan', 'activeSubscription.plan', 'users', 'invoices', 'customers'])
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->plan, fn ($q) => $q->whereHas('plan', fn ($p) => $p->where('slug', $request->plan)))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Append _count attributes to mimic withCount() output
        $tenants->each(function ($tenant) {
            $tenant->invoices_count  = $tenant->invoices->count();
            $tenant->customers_count = $tenant->customers->count();
            $tenant->users_count     = $tenant->users->count();
            // Unset loaded collections to keep payload size manageable
            unset($tenant->invoices, $tenant->customers);
        });

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
            'plans'   => Plan::orderBy('sort_order')->get(['id', 'name', 'slug']),
            'filters' => $request->only(['search', 'plan']),
        ]);
    }

    public function store(Request $request)
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
                'name'             => $request->company_name,
                'gstin'            => $request->gstin ? strtoupper($request->gstin) : null,
                'state'            => $request->state,
                'invoice_prefix'   => strtoupper($request->invoice_prefix ?? 'INV'),
                'subscription_plan'=> 'free',
                'plan_id'          => $request->plan_id,
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

        return redirect()->route('admin.tenants.index')->with('success', "Tenant '{$request->company_name}' created successfully.");
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['plan', 'users', 'subscriptions.plan']);
        // loadCount() is not supported by MongoDB; count via relationship queries
        $invoiceCount  = $tenant->invoices()->count();
        $customerCount = $tenant->customers()->count();

        return Inertia::render('Admin/Tenants/Show', [
            'tenant' => $tenant,
            'plans'  => Plan::orderBy('sort_order')->get(),
            'stats'  => [
                'total_revenue'   => $tenant->payments()->sum('amount'),
                'invoice_count'   => $invoiceCount,
                'customer_count'  => $customerCount,
                'monthly_invoices'=> $tenant->monthly_invoice_count,
            ],
        ]);
    }

    public function assignPlan(Request $request, Tenant $tenant)
    {
        $request->validate(['plan_id' => 'required|exists:plans,id']);

        $plan = Plan::findOrFail($request->plan_id);
        $tenant->update(['plan_id' => $plan->id]);

        // Create subscription record
        $tenant->subscriptions()->create([
            'plan_id'   => $plan->id,
            'status'    => 'active',
            'starts_at' => now(),
            'ends_at'   => now()->addMonth(),
        ]);

        return back()->with('success', "Plan changed to {$plan->name}.");
    }

    public function update(Request $request, Tenant $tenant)
    {
        $ownerUser = $tenant->users()->where('role', 'owner')->first();

        $request->validate([
            'company_name'   => 'required|string|max:255',
            'owner_name'     => 'required|string|max:255',
            'email'          => [
                'required', 'email',
                // MongoDB stores integer PKs in 'id' field; use where() instead of ignore()
                // which resolves to _id (the ObjectId) and would not match
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
            $ownerData = [
                'name'  => $request->owner_name,
                'email' => $request->email,
            ];
            if ($request->filled('new_password')) {
                $ownerData['password'] = Hash::make($request->new_password);
            }
            $ownerUser->update($ownerData);
        }

        return back()->with('success', "Tenant '{$tenant->name}' updated successfully.");
    }

    public function toggleSuspend(Tenant $tenant)
    {
        $tenant->update(['is_suspended' => !$tenant->is_suspended]);
        $status = $tenant->is_suspended ? 'suspended' : 'reactivated';
        return back()->with('success', "Tenant {$status} successfully.");
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();
        return redirect()->route('admin.tenants.index')->with('success', 'Tenant deleted.');
    }
}
