<?php

namespace App\Models;

use App\Traits\HasIntegerPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringInvoice extends Model
{
    use HasFactory, HasIntegerPrimaryKey;

    protected $fillable = [
        'tenant_id', 'customer_id', 'title', 'frequency',
        'start_date', 'end_date', 'next_run_date', 'last_run_date',
        'invoice_type', 'supply_type', 'due_days', 'notes', 'terms',
        'is_active', 'items',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'end_date'      => 'date',
        'next_run_date' => 'date',
        'last_run_date' => 'date',
        'is_active'     => 'boolean',
        'items'         => 'array',
    ];

    public function tenant(): BelongsTo   { return $this->belongsTo(Tenant::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }

    public function nextRunAfter(): \Carbon\Carbon
    {
        return match ($this->frequency) {
            'weekly'    => $this->next_run_date->addWeek(),
            'quarterly' => $this->next_run_date->addMonths(3),
            'yearly'    => $this->next_run_date->addYear(),
            default     => $this->next_run_date->addMonth(),
        };
    }
}
