<?php

namespace App\Models;

use App\Traits\HasIntegerPrimaryKey;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    use HasIntegerPrimaryKey;

    protected $fillable = [
        'quotation_id', 'product_id', 'description', 'hsn_sac_code', 'unit',
        'quantity', 'price', 'discount_percent',
        'taxable_amount', 'gst_rate',
        'cgst_rate', 'sgst_rate', 'igst_rate',
        'cgst_amount', 'sgst_amount', 'igst_amount',
        'total_amount', 'sort_order',
    ];

    protected $casts = [
        'quantity'       => 'float',
        'price'          => 'float',
        'discount_percent'=> 'float',
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

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
