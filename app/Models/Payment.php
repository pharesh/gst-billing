<?php

namespace App\Models;

use App\Traits\HasIntegerPrimaryKey;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasIntegerPrimaryKey;
    protected $fillable = [
        'tenant_id', 'invoice_id', 'amount', 'payment_date',
        'payment_method', 'reference_number', 'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'float',
    ];

    protected static function booted(): void
    {
        static::saved(function (Payment $payment) {
            $payment->invoice->updatePaymentStatus();
        });

        static::deleted(function (Payment $payment) {
            $payment->invoice->updatePaymentStatus();
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
