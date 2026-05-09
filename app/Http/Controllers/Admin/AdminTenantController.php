<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminTenantController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::with(['plan', 'activeSubscription.plan'])
            ->withCount(['invoices', 'customers', 'users'])
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->plan, fn ($q) => $q->whereHas('plan', fn ($p) => $p->where('slug', $request->plan)))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
            'plans'   => Plan::orderBy('sort_order')->get(['id', 'name', 'slug']),
            'filters' => $request->only(['search', 'plan']),
        ]);
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['plan', 'users', 'subscriptions.plan']);
        $tenant->loadCount(['invoices', 'customers', 'payments']);

        return Inertia::render('Admin/Tenants/Show', [
            'tenant' => $tenant,
            'plans'  => Plan::orderBy('sort_order')->get(),
            'stats'  => [
                'total_revenue'   => $tenant->payments()->sum('amount'),
                'invoice_count'   => $tenant->invoices_count,
                'customer_count'  => $tenant->customers_count,
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
