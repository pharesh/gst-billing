<?php

namespace App\Models;

use App\Traits\HasIntegerPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DebitNote extends Model
{
    use HasFactory, HasIntegerPrimaryKey;

    protected $fillable = [
        'tenant_id', 'supplier_id', 'purchase_invoice_id',
        'debit_note_number', 'debit_note_date', 'supply_type',
        'reason',
        'subtotal', 'cgst_amount', 'sgst_amount', 'igst_amount',
        'total_amount', 'notes',
    ];

    protected $casts = [
        'debit_note_date' => 'date',
        'subtotal'        => 'float',
        'cgst_amount'     => 'float',
        'sgst_amount'     => 'float',
        'igst_amount'     => 'float',
        'total_amount'    => 'float',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DebitNoteItem::class)->orderBy('sort_order');
    }
}
