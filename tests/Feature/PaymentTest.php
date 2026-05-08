<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function setupInvoice(): array
    {
        $tenant   = Tenant::factory()->create();
        $user     = User::factory()->create(['tenant_id' => $tenant->id]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $invoice  = Invoice::create([
            'tenant_id'      => $tenant->id,
            'customer_id'    => $customer->id,
            'invoice_number' => 'INV-0001',
            'invoice_date'   => now()->toDateString(),
            'invoice_type'   => 'b2b',
            'supply_type'    => 'intrastate',
            'subtotal'       => 1000,
            'cgst_amount'    => 90,
            'sgst_amount'    => 90,
            'total_amount'   => 1180,
            'amount_paid'    => 0,
            'payment_status' => 'unpaid',
        ]);
        return [$user, $tenant, $invoice];
    }

    public function test_can_record_payment(): void
    {
        Mail::fake();
        [$user, $tenant, $invoice] = $this->setupInvoice();

        $this->actingAs($user)->post(route('payments.store', $invoice), [
            'amount'         => 500,
            'payment_date'   => now()->toDateString(),
            'payment_method' => 'upi',
        ])->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount'     => 500,
        ]);
    }

    public function test_payment_updates_invoice_to_partial(): void
    {
        Mail::fake();
        [$user, $tenant, $invoice] = $this->setupInvoice();

        $this->actingAs($user)->post(route('payments.store', $invoice), [
            'amount'         => 500,
            'payment_date'   => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $invoice->refresh();
        $this->assertEquals('partial', $invoice->payment_status);
        $this->assertEquals(500, $invoice->amount_paid);
    }

    public function test_full_payment_marks_invoice_paid(): void
    {
        Mail::fake();
        [$user, $tenant, $invoice] = $this->setupInvoice();

        $this->actingAs($user)->post(route('payments.store', $invoice), [
            'amount'         => 1180,
            'payment_date'   => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ]);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->payment_status);
    }

    public function test_cannot_overpay_invoice(): void
    {
        [$user, , $invoice] = $this->setupInvoice();

        $this->actingAs($user)->post(route('payments.store', $invoice), [
            'amount'         => 9999,
            'payment_date'   => now()->toDateString(),
            'payment_method' => 'cash',
        ])->assertSessionHasErrors(['amount']);
    }

    public function test_cannot_record_payment_for_another_tenants_invoice(): void
    {
        [$user] = $this->setupInvoice();
        [,, $otherInvoice] = $this->setupInvoice(); // different tenant

        $this->actingAs($user)->post(route('payments.store', $otherInvoice), [
            'amount'         => 100,
            'payment_date'   => now()->toDateString(),
            'payment_method' => 'cash',
        ])->assertForbidden();
    }

    public function test_can_delete_payment(): void
    {
        Mail::fake();
        [$user, $tenant, $invoice] = $this->setupInvoice();
        $payment = Payment::create([
            'tenant_id'      => $tenant->id,
            'invoice_id'     => $invoice->id,
            'amount'         => 500,
            'payment_date'   => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $this->actingAs($user)
            ->delete(route('payments.destroy', $payment))
            ->assertRedirect();

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }
}
