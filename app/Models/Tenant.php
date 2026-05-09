<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'gstin', 'address', 'city', 'state', 'state_code',
        'pincode', 'phone', 'email', 'logo', 'signature', 'bank_details',
        'subscription_plan', 'subscription_ends_at', 'invoice_prefix',
        'plan_id', 'monthly_invoice_count', 'monthly_count_reset_at', 'is_suspended',
    ];

    protected $casts = [
        'subscription_ends_at'   => 'datetime',
        'monthly_count_reset_at' => 'datetime',
        'bank_details'           => 'array',
        'is_suspended'           => 'boolean',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function currentPlan(): Plan
    {
        return $this->plan ?? Plan::free() ?? new Plan([
            'name'            => 'Free',
            'slug'            => 'free',
            'invoice_limit'   => 10,
            'customer_limit'  => 10,
            'product_limit'   => 10,
            'whatsapp_enabled'=> false,
            'gstr_export'     => false,
            'pdf_download'    => true,
        ]);
    }

    public function canCreateInvoice(): bool
    {
        $plan = $this->currentPlan();
        if ($plan->invoice_limit === -1) return true;

        $this->resetMonthlyCountIfNeeded();
        return $this->monthly_invoice_count < $plan->invoice_limit;
    }

    public function canCreateCustomer(): bool
    {
        $plan = $this->currentPlan();
        if ($plan->customer_limit === -1) return true;

        return $this->customers()->count() < $plan->customer_limit;
    }

    public function canCreateProduct(): bool
    {
        $plan = $this->currentPlan();
        if ($plan->product_limit === -1) return true;

        return $this->products()->count() < $plan->product_limit;
    }

    public function incrementMonthlyInvoiceCount(): void
    {
        $this->resetMonthlyCountIfNeeded();
        $this->increment('monthly_invoice_count');
    }

    private function resetMonthlyCountIfNeeded(): void
    {
        if (!$this->monthly_count_reset_at || $this->monthly_count_reset_at->isLastMonth() || $this->monthly_count_reset_at->lt(now()->startOfMonth())) {
            $this->update([
                'monthly_invoice_count'  => 0,
                'monthly_count_reset_at' => now()->startOfMonth(),
            ]);
        }
    }

    public function isSubscriptionActive(): bool
    {
        return $this->activeSubscription?->isActive() ?? false;
    }

    public function nextInvoiceNumber(): string
    {
        $last = Invoice::where('tenant_id', $this->id)
            ->where('invoice_number', 'like', $this->invoice_prefix . '-%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(invoice_number, "-", -1) AS UNSIGNED) DESC')
            ->value('invoice_number');

        $count = $last ? ((int) substr(strrchr($last, '-'), 1)) + 1 : 1;

        return $this->invoice_prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
