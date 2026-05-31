<?php

namespace App\Models;

use App\Traits\HasIntegerPrimaryKey;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasIntegerPrimaryKey;
    protected $fillable = [
        'name', 'slug', 'price_monthly', 'invoice_limit', 'customer_limit',
        'product_limit', 'whatsapp_enabled', 'gstr_export', 'pdf_download',
        'multi_user', 'features', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'features'          => 'array',
        'whatsapp_enabled'  => 'boolean',
        'gstr_export'       => 'boolean',
        'pdf_download'      => 'boolean',
        'multi_user'        => 'boolean',
        'is_active'         => 'boolean',
        'price_monthly'     => 'float',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function isFree(): bool
    {
        return $this->price_monthly == 0;
    }

    public function hasUnlimitedInvoices(): bool
    {
        return $this->invoice_limit === -1;
    }

    public function hasUnlimitedCustomers(): bool
    {
        return $this->customer_limit === -1;
    }

    public static function free(): ?self
    {
        return static::where('slug', 'free')->first();
    }
}
