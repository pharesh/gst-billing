<?php

namespace App\Models;

use App\Traits\HasIntegerPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditNote extends Model
{
    use HasFactory, HasIntegerPrimaryKey;

    protected $fillable = [
        'tenant_id', 'customer_id', 'invoice_id', 'credit_note_number',
        'credit_note_date', 'reason', 'supply_type', 'status', 'notes',
        'subtotal', 'cgst_amount', 'sgst_amount', 'igst_amount', 'total_amount',
    ];

    protected $casts = [
        'credit_note_date' => 'date',
        'subtotal'         => 'float',
        'cgst_amount'      => 'float',
        'sgst_amount'      => 'float',
        'igst_amount'      => 'float',
        'total_amount'     => 'float',
    ];

    public function tenant(): BelongsTo   { return $this->belongsTo(Tenant::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function invoice(): BelongsTo  { return $this->belongsTo(Invoice::class); }
    public function items(): HasMany      { return $this->hasMany(CreditNoteItem::class)->orderBy('sort_order'); }
}
