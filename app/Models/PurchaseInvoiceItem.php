<?php

namespace App\Models;

use App\Traits\HasIntegerPrimaryKey;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends Model
{
    use HasIntegerPrimaryKey;

    protected $fillable = [
        'purchase_invoice_id', 'description', 'hsn_sac_code', 'unit',
        'quantity', 'price', 'taxable_amount', 'gst_rate',
        'cgst_rate', 'sgst_rate', 'igst_rate',
        'cgst_amount', 'sgst_amount', 'igst_amount',
        'total_amount', 'sort_order',
    ];

    protected $casts = [
        'quantity'       => 'float',
        'price'          => 'float',
        'taxable_amount' => 'float',
        'gst_rate'       => 'float',
        'cgst_rate'      => 'float',
        'sgst_rate'      => 'float',
        'igst_rate'      => 'float',
        'cgst_amount'    => 'float',
        'sgst_amount'    => 'float',
        'igst_amount'    => 'float',
        'total_amount'   => 'float',
        'sort_order'     => 'int',
    ];

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }
}
