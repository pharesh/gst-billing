<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function store(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->tenant_id === $request->user()->tenant_id, 403);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->balance_due,
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,upi,cheque,card,other',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        $payment = Payment::create([
            ...$validated,
            'tenant_id' => $request->user()->tenant_id,
            'invoice_id' => $invoice->id,
        ]);

        $invoice->load(['customer', 'tenant', 'payments']);
        $this->notifications->sendPaymentConfirmed($invoice, $payment);

        return back()->with('success', 'Payment recorded.');
    }

    public function destroy(Request $request, Payment $payment)
    {
        abort_unless($payment->tenant_id === $request->user()->tenant_id, 403);
        $payment->delete();
        return back()->with('success', 'Payment removed.');
    }
}
