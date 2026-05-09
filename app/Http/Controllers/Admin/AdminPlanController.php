<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminPlanController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Plans/Index', [
            'plans' => Plan::withCount('tenants')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'slug'             => 'required|string|unique:plans,slug',
            'price_monthly'    => 'required|numeric|min:0',
            'invoice_limit'    => 'required|integer|min:-1',
            'customer_limit'   => 'required|integer|min:-1',
            'product_limit'    => 'required|integer|min:-1',
            'whatsapp_enabled' => 'boolean',
            'gstr_export'      => 'boolean',
            'pdf_download'     => 'boolean',
            'multi_user'       => 'boolean',
            'features'         => 'nullable|array',
            'sort_order'       => 'integer',
        ]);

        Plan::create($validated);
        return back()->with('success', 'Plan created.');
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'price_monthly'    => 'required|numeric|min:0',
            'invoice_limit'    => 'required|integer|min:-1',
            'customer_limit'   => 'required|integer|min:-1',
            'product_limit'    => 'required|integer|min:-1',
            'whatsapp_enabled' => 'boolean',
            'gstr_export'      => 'boolean',
            'pdf_download'     => 'boolean',
            'multi_user'       => 'boolean',
            'features'         => 'nullable|array',
            'is_active'        => 'boolean',
            'sort_order'       => 'integer',
        ]);

        $plan->update($validated);
        return back()->with('success', 'Plan updated.');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->tenants()->count() > 0) {
            return back()->withErrors(['plan' => 'Cannot delete plan with active tenants.']);
        }
        $plan->delete();
        return back()->with('success', 'Plan deleted.');
    }
}
