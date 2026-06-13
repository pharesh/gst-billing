<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $totalTenants = Tenant::count();
        $activeSubs   = Subscription::where('status', 'active')->count();

        $mrr = Subscription::where('status', 'active')
            ->with('plan:id,price_monthly')
            ->get()
            ->sum(fn ($s) => $s->plan?->price_monthly ?? 0);

        $newThisMonth = Tenant::where('created_at', '>=', now()->startOfMonth()->toDateTimeString())
            ->where('created_at', '<=', now()->endOfMonth()->toDateTimeString())
            ->count();

        $recentTenants = Tenant::with(['plan', 'activeSubscription.plan'])
            ->latest()
            ->limit(10)
            ->get();

        $recentSubscriptions = Subscription::with(['tenant', 'plan'])
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => [
                'total_tenants'  => $totalTenants,
                'active_subs'    => $activeSubs,
                'mrr'            => $mrr,
                'new_this_month' => $newThisMonth,
            ],
            'recentTenants'       => $recentTenants,
            'recentSubscriptions' => $recentSubscriptions,
        ]);
    }
}
