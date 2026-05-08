<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'tenant_id'     => Tenant::factory(),
            'name'          => $this->faker->company(),
            'customer_type' => 'b2b',
            'gstin'         => strtoupper($this->faker->bothify('24??#####??###?')),
            'address'       => $this->faker->streetAddress(),
            'city'          => $this->faker->city(),
            'state'         => 'Gujarat',
            'state_code'    => '24',
            'pincode'       => $this->faker->numerify('######'),
            'phone'         => '9' . $this->faker->numerify('#########'),
            'email'         => $this->faker->companyEmail(),
        ];
    }

    public function b2c(): static
    {
        return $this->state([
            'customer_type' => 'b2c',
            'gstin'         => null,
        ]);
    }
}
