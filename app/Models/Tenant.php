<?php

namespace App\Models;

use App\Traits\HasIntegerPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use HasFactory, HasIntegerPrimaryKey;

    protected $fillable = [
        'name', 'gstin', 'address', 'city', 'state', 'state_code',
        'pincode', 'phone', 'email', 'logo', 'signature', 'bank_details',
        'subscription_plan', 'subscription_ends_at', 'invoice_prefix',
        'plan_id', 'monthly_invoice_count', 'monthly_count_reset_at', 'is_suspended',
    ];

    protected $casts = [
        'subscription_ends_at'   => 'datetime',
        'monthly_count_reset_at' => 'datetime',
        'is_suspended'           => 'boolean',
        // bank_details: stored as BSON document in MongoDB — no cast needed
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latest();
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

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
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

    public function nextCreditNoteNumber(): string
    {
        $prefix = $this->invoice_prefix . '-CN';
        $max = \App\Models\CreditNote::where('tenant_id', $this->id)
            ->where('credit_note_number', 'like', $prefix . '-%')
            ->pluck('credit_note_number')
            ->map(fn ($n) => (int) substr(strrchr($n, '-'), 1))
            ->max();

        $count = ($max ?? 0) + 1;

        return $prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function nextInvoiceNumber(): string
    {
        $max = Invoice::where('tenant_id', $this->id)
            ->where('invoice_number', 'like', $this->invoice_prefix . '-%')
            ->pluck('invoice_number')
            ->map(fn ($n) => (int) substr(strrchr($n, '-'), 1))
            ->max();

        $count = ($max ?? 0) + 1;

        return $this->invoice_prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
