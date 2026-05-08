<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'gstin', 'address', 'city', 'state', 'state_code',
        'pincode', 'phone', 'email', 'logo', 'signature', 'bank_details',
        'subscription_plan', 'subscription_ends_at', 'invoice_prefix',
    ];

    protected $casts = [
        'subscription_ends_at' => 'datetime',
        'bank_details' => 'array',
    ];

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

    public function isSubscriptionActive(): bool
    {
        return $this->subscription_plan !== 'free'
            && $this->subscription_ends_at?->isFuture();
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
