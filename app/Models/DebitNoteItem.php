<?php

namespace App\Models;

use App\Traits\HasIntegerPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebitNoteItem extends Model
{
    use HasFactory, HasIntegerPrimaryKey;

    protected $fillable = [
        'debit_note_id', 'description', 'hsn_sac_code', 'unit', 'quantity',
        'price', 'gst_rate', 'taxable_amount',
        'cgst_rate', 'cgst_amount', 'sgst_rate', 'sgst_amount',
        'igst_rate', 'igst_amount', 'total_amount', 'sort_order',
    ];

    protected $casts = [
        'quantity'       => 'float',
        'price'          => 'float',
        'gst_rate'       => 'float',
        'taxable_amount' => 'float',
        'cgst_rate'      => 'float',
        'cgst_amount'    => 'float',
        'sgst_rate'      => 'float',
        'sgst_amount'    => 'float',
        'igst_rate'      => 'float',
        'igst_amount'    => 'float',
        'total_amount'   => 'float',
    ];

    public function debitNote(): BelongsTo
    {
        return $this->belongsTo(DebitNote::class);
    }
}
