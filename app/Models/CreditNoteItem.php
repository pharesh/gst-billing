<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteItem extends Model
{
    protected $fillable = [
        'credit_note_id', 'description', 'hsn_sac_code', 'unit',
        'quantity', 'price', 'gst_rate',
        'taxable_amount', 'cgst_amount', 'sgst_amount', 'igst_amount', 'total_amount',
        'sort_order',
    ];

    protected $casts = [
        'quantity'       => 'float',
        'price'          => 'float',
        'gst_rate'       => 'float',
        'taxable_amount' => 'float',
        'cgst_amount'    => 'float',
        'sgst_amount'    => 'float',
        'igst_amount'    => 'float',
        'total_amount'   => 'float',
    ];

    public function creditNote(): BelongsTo { return $this->belongsTo(CreditNote::class); }
}
