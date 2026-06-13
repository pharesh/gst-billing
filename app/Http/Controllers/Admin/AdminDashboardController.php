<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $totalTenants = Tenant::count();

        // Mirror the isActive() logic: status in active/trial AND (ends_at is null OR ends_at is in the future)
        $activeSubs = Subscription::whereIn('status', ['active', 'trial'])
            ->where(function ($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', now()->toDateTimeString());
            })
            ->count();

        $mrr = Subscription::whereIn('status', ['active', 'trial'])
            ->where(function ($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', now()->toDateTimeString());
            })
            ->with('plan:id,price_monthly')
            ->get()
            ->sum(fn ($s) => $s->plan?->price_monthly ?? 0);

        $newThisMonth = Tenant::where('created_at', '>=', now()->startOfMonth()->toDateTimeString())
            ->where('created_at', '<=', now()->endOfMonth()->toDateTimeString())
            ->count();

        $recentTenants = Tenant::with(['plan', 'activeSubscription.plan', 'users'])
            ->latest()
            ->limit(10)
            ->get();

        $recentSubscriptions = Subscription::with(['tenant', 'plan'])
            ->latest()
            ->limit(10)
            ->get();

        // Build owner email map: tenant_id => owner email
        $tenantIds    = $recentTenants->pluck('id')->all();
        $ownerEmails  = User::whereIn('tenant_id', $tenantIds)
            ->where('role', 'owner')
            ->get(['tenant_id', 'email'])
            ->keyBy('tenant_id');

        $formattedTenants = $recentTenants->map(function ($tenant) use ($ownerEmails) {
            return [
                'id'                    => $tenant->id,
                'name'                  => $tenant->name,
                'email'                 => $tenant->email,
                'owner_email'           => $ownerEmails[$tenant->id]?->email ?? null,
                'gstin'                 => $tenant->gstin,
                'phone'                 => $tenant->phone,
                'city'                  => $tenant->city,
                'state'                 => $tenant->state,
                'is_suspended'          => $tenant->is_suspended,
                'plan_id'               => $tenant->plan_id,
                'plan'                  => $tenant->plan ? [
                    'id'            => $tenant->plan->id,
                    'name'          => $tenant->plan->name,
                    'slug'          => $tenant->plan->slug,
                    'price_monthly' => $tenant->plan->price_monthly,
                ] : null,
                'active_subscription'   => $tenant->activeSubscription ? [
                    'id'        => $tenant->activeSubscription->id,
                    'status'    => $tenant->activeSubscription->status,
                    'starts_at' => $tenant->activeSubscription->starts_at?->toDateTimeString(),
                    'ends_at'   => $tenant->activeSubscription->ends_at?->toDateTimeString(),
                    'plan'      => $tenant->activeSubscription->plan ? [
                        'id'            => $tenant->activeSubscription->plan->id,
                        'name'          => $tenant->activeSubscription->plan->name,
                        'price_monthly' => $tenant->activeSubscription->plan->price_monthly,
                    ] : null,
                ] : null,
                'subscription_ends_at'  => $tenant->subscription_ends_at?->toDateTimeString(),
                'monthly_count_reset_at'=> $tenant->monthly_count_reset_at?->toDateTimeString(),
                'created_at'            => $tenant->created_at?->toDateTimeString(),
                'updated_at'            => $tenant->updated_at?->toDateTimeString(),
            ];
        });

        $formattedSubscriptions = $recentSubscriptions->map(function ($sub) {
            return [
                'id'                        => $sub->id,
                'tenant_id'                 => $sub->tenant_id,
                'plan_id'                   => $sub->plan_id,
                'status'                    => $sub->status,
                'amount_paid'               => $sub->amount_paid,
                'razorpay_order_id'         => $sub->razorpay_order_id,
                'razorpay_payment_id'       => $sub->razorpay_payment_id,
                'razorpay_subscription_id'  => $sub->razorpay_subscription_id,
                'starts_at'                 => $sub->starts_at?->toDateTimeString(),
                'ends_at'                   => $sub->ends_at?->toDateTimeString(),
                'created_at'                => $sub->created_at?->toDateTimeString(),
                'updated_at'                => $sub->updated_at?->toDateTimeString(),
                'tenant'                    => $sub->tenant ? [
                    'id'    => $sub->tenant->id,
                    'name'  => $sub->tenant->name,
                    'email' => $sub->tenant->email,
                ] : null,
                'plan'                      => $sub->plan ? [
                    'id'            => $sub->plan->id,
                    'name'          => $sub->plan->name,
                    'price_monthly' => $sub->plan->price_monthly,
                ] : null,
            ];
        });

        return response()->json([
            'stats' => [
                'total_tenants'  => $totalTenants,
                'active_subs'    => $activeSubs,
                'mrr'            => $mrr,
                'new_this_month' => $newThisMonth,
            ],
            'recentTenants'       => $formattedTenants,
            'recentSubscriptions' => $formattedSubscriptions,
        ]);
    }
}
