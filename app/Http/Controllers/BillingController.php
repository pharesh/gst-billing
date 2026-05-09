<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        $tenant->load(['plan', 'activeSubscription.plan', 'subscriptions.plan']);

        return Inertia::render('Billing/Index', [
            'plans'              => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'currentPlan'        => $tenant->currentPlan(),
            'activeSubscription' => $tenant->activeSubscription,
            'subscriptions'      => $tenant->subscriptions()->with('plan')->latest()->limit(5)->get(),
            'usage'              => [
                'monthly_invoices' => $tenant->monthly_invoice_count,
                'customers'        => $tenant->customers()->count(),
                'products'         => $tenant->products()->count(),
            ],
            'razorpayKey'        => config('services.razorpay.key'),
        ]);
    }

    public function createOrder(Request $request)
    {
        $request->validate(['plan_id' => 'required|exists:plans,id']);

        $plan = Plan::findOrFail($request->plan_id);

        if ($plan->isFree()) {
            return back()->withErrors(['plan' => 'Cannot purchase free plan.']);
        }

        $tenant = $request->user()->tenant;

        // Create Razorpay order
        $api = new \Razorpay\Api\Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );

        $order = $api->order->create([
            'receipt'  => 'sub_' . $tenant->id . '_' . time(),
            'amount'   => $plan->price_monthly * 100, // paise
            'currency' => 'INR',
            'notes'    => [
                'tenant_id' => $tenant->id,
                'plan_id'   => $plan->id,
            ],
        ]);

        // Store pending subscription
        $subscription = $tenant->subscriptions()->create([
            'plan_id'            => $plan->id,
            'status'             => 'trial',
            'razorpay_order_id'  => $order->id,
        ]);

        return response()->json([
            'order_id'       => $order->id,
            'amount'         => $plan->price_monthly * 100,
            'currency'       => 'INR',
            'plan_name'      => $plan->name,
            'subscription_id'=> $subscription->id,
        ]);
    }

    public function verifyPayment(Request $request)
    {
        $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
            'subscription_id'     => 'required|exists:subscriptions,id',
        ]);

        // Verify signature
        $expectedSignature = hash_hmac(
            'sha256',
            $request->razorpay_order_id . '|' . $request->razorpay_payment_id,
            config('services.razorpay.secret')
        );

        if ($expectedSignature !== $request->razorpay_signature) {
            return back()->withErrors(['payment' => 'Payment verification failed.']);
        }

        $subscription = Subscription::findOrFail($request->subscription_id);
        $plan         = $subscription->plan;
        $tenant       = $request->user()->tenant;

        $subscription->update([
            'status'              => 'active',
            'starts_at'           => now(),
            'ends_at'             => now()->addMonth(),
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'amount_paid'         => $plan->price_monthly,
        ]);

        $tenant->update(['plan_id' => $plan->id]);

        return redirect()->route('billing.index')->with('success', "Upgraded to {$plan->name} plan successfully!");
    }
}
