<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = [
        'tenant_id', 'name', 'gstin', 'customer_type',
        'address', 'city', 'state', 'state_code', 'pincode', 'phone', 'email',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function totalOutstanding(): float
    {
        return $this->invoices()
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->selectRaw('SUM(total_amount - amount_paid) as outstanding')
            ->value('outstanding') ?? 0;
    }
}
