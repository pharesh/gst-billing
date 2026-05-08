<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();

        return [
            'tenant_id'      => $tenant->id,
            'customer_id'    => Customer::factory()->create(['tenant_id' => $tenant->id])->id,
            'invoice_number' => $tenant->invoice_prefix . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'invoice_type'   => 'b2b',
            'supply_type'    => 'intrastate',
            'subtotal'       => 1000.00,
            'cgst_amount'    => 90.00,
            'sgst_amount'    => 90.00,
            'igst_amount'    => 0.00,
            'discount_amount'=> 0.00,
            'total_amount'   => 1180.00,
            'amount_paid'    => 0.00,
            'payment_status' => 'unpaid',
        ];
    }

    public function paid(): static
    {
        return $this->state([
            'amount_paid'    => 1180.00,
            'payment_status' => 'paid',
        ]);
    }

    public function overdue(): static
    {
        return $this->state([
            'due_date'       => now()->subDays(5)->toDateString(),
            'payment_status' => 'unpaid',
        ]);
    }

    public function partial(): static
    {
        return $this->state([
            'amount_paid'    => 500.00,
            'payment_status' => 'partial',
        ]);
    }
}
