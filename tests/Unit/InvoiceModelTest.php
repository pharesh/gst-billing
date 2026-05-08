<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceModelTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoice(array $attrs = []): Invoice
    {
        $tenant   = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        return Invoice::create(array_merge([
            'tenant_id'      => $tenant->id,
            'customer_id'    => $customer->id,
            'invoice_number' => 'TEST-0001',
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'invoice_type'   => 'b2b',
            'supply_type'    => 'intrastate',
            'subtotal'       => 1000,
            'cgst_amount'    => 90,
            'sgst_amount'    => 90,
            'igst_amount'    => 0,
            'total_amount'   => 1180,
            'amount_paid'    => 0,
            'payment_status' => 'unpaid',
        ], $attrs));
    }

    // ─── balance_due accessor ─────────────────────────────────────────────

    public function test_balance_due_equals_total_when_nothing_paid(): void
    {
        $invoice = $this->makeInvoice(['total_amount' => 1180, 'amount_paid' => 0]);
        $this->assertEquals(1180.00, $invoice->balance_due);
    }

    public function test_balance_due_is_zero_when_fully_paid(): void
    {
        $invoice = $this->makeInvoice(['total_amount' => 1180, 'amount_paid' => 1180]);
        $this->assertEquals(0.00, $invoice->balance_due);
    }

    public function test_balance_due_shows_remaining_after_partial_payment(): void
    {
        $invoice = $this->makeInvoice(['total_amount' => 1180, 'amount_paid' => 500]);
        $this->assertEquals(680.00, $invoice->balance_due);
    }

    // ─── isOverdue ────────────────────────────────────────────────────────

    public function test_overdue_when_unpaid_and_past_due_date(): void
    {
        $invoice = $this->makeInvoice([
            'due_date'       => now()->subDays(1)->toDateString(),
            'payment_status' => 'unpaid',
        ]);
        $this->assertTrue($invoice->isOverdue());
    }

    public function test_not_overdue_when_paid_even_if_past_due_date(): void
    {
        $invoice = $this->makeInvoice([
            'due_date'       => now()->subDays(1)->toDateString(),
            'payment_status' => 'paid',
        ]);
        $this->assertFalse($invoice->isOverdue());
    }

    public function test_not_overdue_when_due_date_in_future(): void
    {
        $invoice = $this->makeInvoice([
            'due_date'       => now()->addDays(10)->toDateString(),
            'payment_status' => 'unpaid',
        ]);
        $this->assertFalse($invoice->isOverdue());
    }

    public function test_not_overdue_when_no_due_date(): void
    {
        $invoice = $this->makeInvoice(['due_date' => null]);
        $this->assertFalse($invoice->isOverdue());
    }

    // ─── updatePaymentStatus ──────────────────────────────────────────────

    public function test_status_becomes_paid_when_fully_paid(): void
    {
        $invoice = $this->makeInvoice(['total_amount' => 1180]);
        Payment::create([
            'tenant_id'      => $invoice->tenant_id,
            'invoice_id'     => $invoice->id,
            'amount'         => 1180,
            'payment_date'   => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->payment_status);
    }

    public function test_status_becomes_partial_when_partially_paid(): void
    {
        $invoice = $this->makeInvoice(['total_amount' => 1180]);
        Payment::create([
            'tenant_id'      => $invoice->tenant_id,
            'invoice_id'     => $invoice->id,
            'amount'         => 500,
            'payment_date'   => now()->toDateString(),
            'payment_method' => 'upi',
        ]);

        $invoice->refresh();
        $this->assertEquals('partial', $invoice->payment_status);
        $this->assertEquals(500, $invoice->amount_paid);
    }

    public function test_status_reverts_to_unpaid_when_payment_deleted(): void
    {
        $invoice = $this->makeInvoice(['total_amount' => 1180]);
        $payment = Payment::create([
            'tenant_id'      => $invoice->tenant_id,
            'invoice_id'     => $invoice->id,
            'amount'         => 1180,
            'payment_date'   => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $payment->delete();
        $invoice->refresh();

        $this->assertEquals('unpaid', $invoice->payment_status);
        $this->assertEquals(0, $invoice->amount_paid);
    }
}
