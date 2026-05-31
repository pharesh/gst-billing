<?php

namespace App\Models;

use App\Traits\HasIntegerPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, HasIntegerPrimaryKey;
    protected $fillable = [
        'tenant_id', 'customer_id', 'invoice_number', 'invoice_date', 'due_date',
        'invoice_type', 'supply_type',
        'subtotal', 'cgst_amount', 'sgst_amount', 'igst_amount',
        'discount_amount', 'total_amount', 'amount_paid',
        'payment_status', 'notes', 'terms',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'float',
        'cgst_amount' => 'float',
        'sgst_amount' => 'float',
        'igst_amount' => 'float',
        'discount_amount' => 'float',
        'total_amount' => 'float',
        'amount_paid' => 'float',
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
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getBalanceDueAttribute(): float
    {
        return round($this->total_amount - $this->amount_paid, 2);
    }

    public function isOverdue(): bool
    {
        return $this->payment_status !== 'paid'
            && $this->due_date?->isPast();
    }

    public function updatePaymentStatus(): void
    {
        $paid = $this->payments()->sum('amount');
        $this->amount_paid = $paid;

        if ($paid <= 0) {
            $this->payment_status = 'unpaid';
        } elseif ($paid >= $this->total_amount) {
            $this->payment_status = 'paid';
        } else {
            $this->payment_status = 'partial';
        }

        $this->save();
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
