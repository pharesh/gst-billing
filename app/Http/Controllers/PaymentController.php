<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function export(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $payments = Payment::with(['invoice.customer'])
            ->where('tenant_id', $tenantId)
            ->when($request->date_from, fn ($q) => $q->where('payment_date', '>=', $request->date_from))
            ->when($request->date_to,   fn ($q) => $q->where('payment_date', '<=', $request->date_to))
            ->when($request->method,    fn ($q) => $q->where('payment_method', $request->method))
            ->latest('payment_date')
            ->get();

        $filename = 'payments-' . now()->format('Y-m-d') . '.csv';

        return response()->stream(function () use ($payments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Payment Date', 'Invoice #', 'Customer', 'Method', 'Reference', 'Amount (₹)', 'Notes']);
            foreach ($payments as $p) {
                fputcsv($handle, [
                    $p->payment_date instanceof \DateTime
                        ? $p->payment_date->format('d/m/Y')
                        : $p->payment_date,
                    $p->invoice?->invoice_number ?? '',
                    $p->invoice?->customer?->name ?? '',
                    ucwords(str_replace('_', ' ', $p->payment_method)),
                    $p->reference_number ?? '',
                    number_format($p->amount, 2, '.', ''),
                    $p->notes ?? '',
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function destroy(Request $request, Payment $payment)
    {
        abort_unless($payment->tenant_id === $request->user()->tenant_id, 403);
        $payment->delete();
        return back()->with('success', 'Payment removed.');
    }
}
