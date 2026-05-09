<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalTenants     = Tenant::count();
        $activeSubs       = Subscription::where('status', 'active')->count();
        $mrr              = Subscription::where('status', 'active')
                                ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
                                ->sum('plans.price_monthly');
        $newThisMonth     = Tenant::whereMonth('created_at', now()->month)->count();

        $recentTenants = Tenant::with(['plan', 'activeSubscription.plan'])
            ->latest()
            ->limit(10)
            ->get();

        $recentSubscriptions = Subscription::with(['tenant', 'plan'])
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_tenants'   => $totalTenants,
                'active_subs'     => $activeSubs,
                'mrr'             => $mrr,
                'new_this_month'  => $newThisMonth,
            ],
            'recentTenants'       => $recentTenants,
            'recentSubscriptions' => $recentSubscriptions,
        ]);
    }
}
