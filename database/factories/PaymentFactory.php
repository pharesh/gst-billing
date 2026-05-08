<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $invoice = Invoice::factory()->create();

        return [
            'tenant_id'        => $invoice->tenant_id,
            'invoice_id'       => $invoice->id,
            'amount'           => 500.00,
            'payment_date'     => now()->toDateString(),
            'payment_method'   => $this->faker->randomElement(['cash', 'bank_transfer', 'upi', 'cheque']),
            'reference_number' => $this->faker->optional()->bothify('UTR##########'),
            'notes'            => null,
        ];
    }
}
