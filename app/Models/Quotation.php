<?php

namespace App\Models;

use App\Traits\HasIntegerPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use HasFactory, HasIntegerPrimaryKey;

    protected $fillable = [
        'tenant_id', 'customer_id', 'quotation_number', 'quotation_date', 'valid_until',
        'invoice_type', 'supply_type',
        'subtotal', 'cgst_amount', 'sgst_amount', 'igst_amount',
        'discount_amount', 'total_amount',
        'notes', 'terms',
        'status', 'converted_invoice_id',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until'    => 'date',
        'subtotal'       => 'float',
        'cgst_amount'    => 'float',
        'sgst_amount'    => 'float',
        'igst_amount'    => 'float',
        'discount_amount'=> 'float',
        'total_amount'   => 'float',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast() && $this->status === 'sent';
    }
}
