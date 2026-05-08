<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsOwner(): array
    {
        $tenant   = Tenant::factory()->create();
        $user     = User::factory()->create(['tenant_id' => $tenant->id]);
        return [$user, $tenant];
    }

    public function test_customers_page_loads_for_authenticated_user(): void
    {
        [$user] = $this->actingAsOwner();

        $this->actingAs($user)
            ->get(route('customers.index'))
            ->assertOk();
    }

    public function test_guest_is_redirected_from_customers(): void
    {
        $this->get(route('customers.index'))
            ->assertRedirect(route('login'));
    }

    public function test_can_create_customer(): void
    {
        [$user, $tenant] = $this->actingAsOwner();

        $this->actingAs($user)->post(route('customers.store'), [
            'name'          => 'Test Company',
            'customer_type' => 'b2b',
            'gstin'         => '24AABCU9603R1ZX',
            'address'       => '123 MG Road',
            'city'          => 'Surat',
            'state'         => 'Gujarat',
            'state_code'    => '24',
            'phone'         => '9876543210',
            'email'         => 'test@company.com',
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'name'      => 'Test Company',
        ]);
    }

    public function test_cannot_create_customer_without_required_fields(): void
    {
        [$user] = $this->actingAsOwner();

        $this->actingAs($user)
            ->post(route('customers.store'), [])
            ->assertSessionHasErrors(['name']);
    }

    public function test_can_update_customer(): void
    {
        [$user, $tenant] = $this->actingAsOwner();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->patch(route('customers.update', $customer), [
            'name'          => 'Updated Name',
            'customer_type' => 'b2c',
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'id'   => $customer->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_cannot_update_another_tenants_customer(): void
    {
        [$user] = $this->actingAsOwner();
        $other = Customer::factory()->create(); // belongs to different tenant

        $this->actingAs($user)
            ->patch(route('customers.update', $other), ['name' => 'Hack'])
            ->assertForbidden();
    }

    public function test_can_delete_customer(): void
    {
        [$user, $tenant] = $this->actingAsOwner();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect();

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_cannot_delete_another_tenants_customer(): void
    {
        [$user] = $this->actingAsOwner();
        $other = Customer::factory()->create();

        $this->actingAs($user)
            ->delete(route('customers.destroy', $other))
            ->assertForbidden();
    }
}
