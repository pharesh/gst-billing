<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name'             => $this->faker->company(),
            'gstin'            => strtoupper($this->faker->bothify('##??#####??###?')),
            'address'          => $this->faker->streetAddress(),
            'city'             => $this->faker->city(),
            'state'            => 'Gujarat',
            'state_code'       => '24',
            'pincode'          => $this->faker->numerify('######'),
            'phone'            => '9' . $this->faker->numerify('#########'),
            'email'            => $this->faker->companyEmail(),
            'invoice_prefix'   => 'INV',
            'subscription_plan'=> 'free',
        ];
    }
}
