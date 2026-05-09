<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPlanLimits
{
    public function handle(Request $request, Closure $next, string $resource)
    {
        $tenant = $request->user()?->tenant;
        if (!$tenant) return $next($request);

        $can = match ($resource) {
            'invoice'  => $tenant->canCreateInvoice(),
            'customer' => $tenant->canCreateCustomer(),
            'product'  => $tenant->canCreateProduct(),
            default    => true,
        };

        if (!$can) {
            $plan = $tenant->currentPlan();
            $message = match ($resource) {
                'invoice'  => "Invoice limit reached ({$plan->invoice_limit}/month). Upgrade your plan to create more invoices.",
                'customer' => "Customer limit reached ({$plan->customer_limit}). Upgrade your plan to add more customers.",
                'product'  => "Product limit reached ({$plan->product_limit}). Upgrade your plan to add more products.",
                default    => 'Plan limit reached. Please upgrade your plan.',
            };

            if ($request->expectsJson() || $request->inertia()) {
                return back()->withErrors(['plan_limit' => $message]);
            }

            return back()->withErrors(['plan_limit' => $message]);
        }

        return $next($request);
    }
}
