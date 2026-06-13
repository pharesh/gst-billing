<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminPlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::orderBy('sort_order')->get();
        $plans->each(fn ($plan) => $plan->tenants_count = $plan->tenants()->count());

        return response()->json(['plans' => $plans]);
    }

    public function store(Request $request): JsonResponse
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

        $plan = Plan::create($validated);

        return response()->json(['message' => 'Plan created.', 'plan' => $plan], 201);
    }

    public function update(Request $request, Plan $plan): JsonResponse
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

        return response()->json(['message' => 'Plan updated.', 'plan' => $plan]);
    }

    public function destroy(Plan $plan): JsonResponse|Response
    {
        if ($plan->tenants()->count() > 0) {
            return response()->json(['errors' => ['plan' => ['Cannot delete plan with active tenants.']]], 422);
        }

        $plan->delete();

        return response()->noContent();
    }
}
