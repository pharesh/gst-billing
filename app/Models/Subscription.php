<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'tenant_id', 'plan_id', 'status', 'starts_at', 'ends_at',
        'razorpay_order_id', 'razorpay_payment_id', 'razorpay_subscription_id', 'amount_paid',
    ];

    protected $casts = [
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
        'amount_paid'=> 'float',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial'])
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }
}
