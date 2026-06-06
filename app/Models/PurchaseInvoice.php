<?php

namespace App\Models;

use App\Traits\HasIntegerPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    use HasFactory, HasIntegerPrimaryKey;

    protected $fillable = [
        'tenant_id', 'supplier_id', 'bill_number', 'bill_date', 'due_date',
        'supply_type',
        'subtotal', 'cgst_amount', 'sgst_amount', 'igst_amount',
        'total_amount', 'amount_paid', 'payment_status',
        'itc_eligible', 'notes',
    ];

    protected $casts = [
        'bill_date'    => 'date',
        'due_date'     => 'date',
        'subtotal'     => 'float',
        'cgst_amount'  => 'float',
        'sgst_amount'  => 'float',
        'igst_amount'  => 'float',
        'total_amount' => 'float',
        'amount_paid'  => 'float',
        'itc_eligible' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class)->orderBy('sort_order');
    }

    public function getBalanceDueAttribute(): float
    {
        return round($this->total_amount - $this->amount_paid, 2);
    }

    public function getItcAmountAttribute(): float
    {
        if (!$this->itc_eligible) return 0.0;
        return round($this->cgst_amount + $this->sgst_amount + $this->igst_amount, 2);
    }
}
