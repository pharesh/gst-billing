<?php

namespace App\Models;

use App\Traits\HasIntegerPrimaryKey;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasIntegerPrimaryKey;
    protected $fillable = [
        'tenant_id', 'name', 'description', 'hsn_sac_code',
        'type', 'unit', 'price', 'gst_rate', 'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'gst_rate' => 'float',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
