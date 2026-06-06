<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Services\EInvoiceService;
use App\Services\GSTCalculationService;
use App\Services\InvoicePDFService;
use App\Services\NotificationService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function __construct(
        private GSTCalculationService $gst,
        private InvoicePDFService $pdfService,
        private WhatsAppService $whatsapp,
        private NotificationService $notifications,
        private EInvoiceService $einvoice
    ) {}

    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;

        $invoices = Invoice::with('customer')
            ->where('tenant_id', $tenant->id)
            ->when($request->search, fn ($q) => $q->where('invoice_number', 'like', "%{$request->search}%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$request->search}%")))
            ->when($request->status,      fn ($q) => $q->where('payment_status', $request->status))
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->date_from,   fn ($q) => $q->where('invoice_date', '>=', $request->date_from))
            ->when($request->date_to,     fn ($q) => $q->where('invoice_date', '<=', $request->date_to))
            ->latest('invoice_date')
            ->paginate(20)
            ->withQueryString();

        $base = Invoice::where('tenant_id', $tenant->id);

        return Inertia::render('Invoices/Index', [
            'invoices'  => $invoices,
            'customers' => Customer::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']),
            'filters'   => $request->only(['search', 'status', 'customer_id', 'date_from', 'date_to']),
            'summary'   => [
                'total'   => (clone $base)->sum('total_amount'),
                'paid'    => (clone $base)->where('payment_status', 'paid')->sum('total_amount'),
                'unpaid'  => (clone $base)->whereIn('payment_status', ['unpaid', 'partial'])->sum('total_amount'),
                'overdue' => (clone $base)->whereIn('payment_status', ['unpaid', 'partial'])
                                ->whereNotNull('due_date')->where('due_date', '<', today()->toDateString())->count(),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $tenant = $request->user()->tenant;

        return Inertia::render('Invoices/Create', [
            'customers' => Customer::where('tenant_id', $tenant->id)->get(['id', 'name', 'gstin', 'state_code', 'customer_type']),
            'products' => Product::where('tenant_id', $tenant->id)->active()->get(['id', 'name', 'price', 'gst_rate', 'unit', 'hsn_sac_code']),
            'tenant' => $tenant->only(['state_code', 'gstin', 'invoice_prefix']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'invoice_type' => 'required|in:b2b,b2c,export',
            'supply_type' => 'required|in:intrastate,interstate',
            'notes' => 'nullable|string|max:500',
            'terms' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.hsn_sac_code' => 'nullable|string',
            'items.*.unit' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.gst_rate' => 'required|numeric|min:0|max:28',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $tenant = $request->user()->tenant;

        DB::transaction(function () use ($validated, $tenant) {
            $invoiceNumber = $tenant->nextInvoiceNumber();

            $calculatedItems = collect($validated['items'])->map(function ($item, $index) use ($validated) {
                $calc = $this->gst->calculateItem(
                    $item['price'],
                    $item['quantity'],
                    $item['gst_rate'],
                    $validated['supply_type'],
                    $item['discount_percent'] ?? 0
                );
                return array_merge($item, $calc, ['sort_order' => $index]);
            });

            $totals = $this->gst->calculateInvoiceTotals($calculatedItems->toArray());

            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $validated['customer_id'],
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'invoice_type' => $validated['invoice_type'],
                'supply_type' => $validated['supply_type'],
                'payment_status' => 'unpaid',
                'amount_paid' => 0,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
                ...$totals,
            ]);

            foreach ($calculatedItems as $item) {
                InvoiceItem::create(array_merge(['invoice_id' => $invoice->id], $item));
            }
        });

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function show(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);

        $invoice->load(['customer', 'items.product', 'payments', 'tenant']);

        return Inertia::render('Invoices/Show', [
            'invoice'        => $invoice,
            'gstGroups'      => $this->gst->groupByGSTRate($invoice->items->map(fn ($i) => $i->toArray())->toArray()),
            'amountInWords'  => $this->gst->amountInWords($invoice->total_amount),
            'razorpayKey'    => config('services.razorpay.key'),
        ]);
    }

    public function download(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);
        return $this->pdfService->download($invoice);
    }

    public function sendWhatsApp(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);

        $sent = $this->whatsapp->sendInvoice($invoice, $this->pdfService);

        return back()->with(
            $sent ? 'success' : 'error',
            $sent ? 'Invoice sent via WhatsApp.' : 'Failed to send WhatsApp message. Check customer phone number.'
        );
    }

    public function sendEmail(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);
        $invoice->load(['customer', 'items', 'tenant', 'payments']);

        $results = $this->notifications->sendInvoice($invoice);

        return back()->with(
            $results['email'] ? 'success' : 'error',
            $results['email'] ? 'Invoice sent via email.' : 'Failed to send email. Check customer email address.'
        );
    }

    public function sendReminder(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);
        $invoice->load(['customer', 'tenant', 'payments']);

        $results = $this->notifications->sendPaymentReminder($invoice);

        $message = match (true) {
            $results['email'] && $results['whatsapp'] => 'Reminder sent via email & WhatsApp.',
            $results['email'] => 'Reminder sent via email.',
            $results['whatsapp'] => 'Reminder sent via WhatsApp.',
            default => 'Failed to send reminder. Check customer contact details.',
        };

        return back()->with(
            ($results['email'] || $results['whatsapp']) ? 'success' : 'error',
            $message
        );
    }

    public function edit(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);
        $invoice->load(['items.product', 'customer']);

        $tenant = $request->user()->tenant;

        return Inertia::render('Invoices/Edit', [
            'invoice'   => $invoice,
            'customers' => Customer::where('tenant_id', $tenant->id)->get(['id', 'name', 'gstin', 'state_code', 'customer_type']),
            'products'  => Product::where('tenant_id', $tenant->id)->active()->get(['id', 'name', 'price', 'gst_rate', 'unit', 'hsn_sac_code']),
            'tenant'    => $tenant->only(['state_code', 'gstin', 'invoice_prefix']),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);

        // Prevent editing paid invoices
        if ($invoice->payment_status === 'paid') {
            return back()->withErrors(['invoice' => 'Cannot edit a paid invoice.']);
        }

        $validated = $request->validate([
            'customer_id'                => 'required|exists:customers,id',
            'invoice_date'               => 'required|date',
            'due_date'                   => 'nullable|date|after_or_equal:invoice_date',
            'invoice_type'               => 'required|in:b2b,b2c,export',
            'supply_type'                => 'required|in:intrastate,interstate',
            'notes'                      => 'nullable|string|max:500',
            'terms'                      => 'nullable|string|max:500',
            'items'                      => 'required|array|min:1',
            'items.*.description'        => 'required|string',
            'items.*.product_id'         => 'nullable|exists:products,id',
            'items.*.hsn_sac_code'       => 'nullable|string',
            'items.*.unit'               => 'required|string',
            'items.*.quantity'           => 'required|numeric|min:0.001',
            'items.*.price'              => 'required|numeric|min:0',
            'items.*.gst_rate'           => 'required|numeric|min:0|max:28',
            'items.*.discount_percent'   => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated, $invoice) {
            $calculatedItems = collect($validated['items'])->map(function ($item, $index) use ($validated) {
                $calc = $this->gst->calculateItem(
                    $item['price'],
                    $item['quantity'],
                    $item['gst_rate'],
                    $validated['supply_type'],
                    $item['discount_percent'] ?? 0
                );
                return array_merge($item, $calc, ['sort_order' => $index]);
            });

            $totals = $this->gst->calculateInvoiceTotals($calculatedItems->toArray());

            $invoice->update([
                'customer_id'  => $validated['customer_id'],
                'invoice_date' => $validated['invoice_date'],
                'due_date'     => $validated['due_date'] ?? null,
                'invoice_type' => $validated['invoice_type'],
                'supply_type'  => $validated['supply_type'],
                'notes'        => $validated['notes'] ?? null,
                'terms'        => $validated['terms'] ?? null,
                ...$totals,
            ]);

            $invoice->items()->delete();
            foreach ($calculatedItems as $item) {
                InvoiceItem::create(array_merge(['invoice_id' => $invoice->id], $item));
            }
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }

    public function generatePaymentLink(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);

        if ($invoice->payment_status === 'paid') {
            return back()->withErrors(['invoice' => 'Invoice is already fully paid.']);
        }

        $key    = config('services.razorpay.key');
        $secret = config('services.razorpay.secret');

        if (blank($key) || str_contains($key, 'YOUR_KEY')) {
            return back()->withErrors(['invoice' => 'Razorpay not configured. Please add keys in Admin → System Settings.']);
        }

        // Return existing active link if already generated
        if ($invoice->razorpay_payment_link_url && $invoice->razorpay_payment_link_status !== 'cancelled') {
            return back()->with('payment_link', $invoice->razorpay_payment_link_url);
        }

        $invoice->load(['customer', 'tenant']);

        try {
            $api = new \Razorpay\Api\Api($key, $secret);

            $linkData = [
                'amount'          => (int) round($invoice->balance_due * 100),
                'currency'        => 'INR',
                'accept_partial'  => false,
                'description'     => 'Invoice ' . $invoice->invoice_number . ' — ' . $invoice->tenant->name,
                'customer'        => [
                    'name'    => $invoice->customer->name,
                    'email'   => $invoice->customer->email ?? '',
                    'contact' => $invoice->customer->phone ?? '',
                ],
                'notify'          => [
                    'sms'   => !empty($invoice->customer->phone),
                    'email' => !empty($invoice->customer->email),
                ],
                'reminder_enable' => true,
                'notes'           => [
                    'invoice_id'     => (string) $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'tenant_id'      => (string) $invoice->tenant_id,
                ],
            ];

            $link = $api->paymentLink->create($linkData);

            $invoice->update([
                'razorpay_payment_link_id'     => $link->id,
                'razorpay_payment_link_url'    => $link->short_url,
                'razorpay_payment_link_status' => $link->status,
            ]);

            return back()->with('payment_link', $link->short_url)->with('success', 'Payment link generated successfully.');
        } catch (\Throwable $e) {
            Log::error('Razorpay payment link error: ' . $e->getMessage());
            return back()->withErrors(['invoice' => 'Failed to generate payment link: ' . $e->getMessage()]);
        }
    }

    public function syncPaymentLink(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);

        if (!$invoice->razorpay_payment_link_id) {
            return back()->withErrors(['invoice' => 'No payment link found. Generate one first.']);
        }

        $key    = config('services.razorpay.key');
        $secret = config('services.razorpay.secret');

        try {
            $api  = new \Razorpay\Api\Api($key, $secret);
            $link = $api->paymentLink->fetch($invoice->razorpay_payment_link_id);

            $invoice->update(['razorpay_payment_link_status' => $link->status]);

            if ($link->status === 'paid') {
                // Find payments made via this link and record them if not already recorded
                $payments = $api->paymentLink->payments($invoice->razorpay_payment_link_id);

                foreach ($payments->items as $p) {
                    if ($p->status === 'captured') {
                        $alreadyRecorded = $invoice->payments()
                            ->where('reference_number', $p->id)
                            ->exists();

                        if (!$alreadyRecorded) {
                            \App\Models\Payment::create([
                                'tenant_id'        => $invoice->tenant_id,
                                'invoice_id'       => $invoice->id,
                                'amount'           => $p->amount / 100,
                                'payment_date'     => now()->toDateString(),
                                'payment_method'   => 'upi',
                                'reference_number' => $p->id,
                                'notes'            => 'Auto-recorded from Razorpay Payment Link',
                            ]);
                        }
                    }
                }

                return back()->with('success', 'Payment synced from Razorpay. Invoice marked as paid.');
            }

            return back()->with('success', 'Payment link status: ' . strtoupper($link->status));
        } catch (\Throwable $e) {
            Log::error('Razorpay sync error: ' . $e->getMessage());
            return back()->withErrors(['invoice' => 'Failed to sync: ' . $e->getMessage()]);
        }
    }

    public function export(Request $request)
    {
        $tenant = $request->user()->tenant;

        $invoices = Invoice::with('customer')
            ->where('tenant_id', $tenant->id)
            ->when($request->search, fn ($q) => $q->where('invoice_number', 'like', "%{$request->search}%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$request->search}%")))
            ->when($request->status,      fn ($q) => $q->where('payment_status', $request->status))
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->date_from,   fn ($q) => $q->where('invoice_date', '>=', $request->date_from))
            ->when($request->date_to,     fn ($q) => $q->where('invoice_date', '<=', $request->date_to))
            ->latest('invoice_date')
            ->get();

        $filename = 'invoices-' . now()->format('Y-m-d') . '.csv';

        return response()->stream(function () use ($invoices) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Invoice #', 'Customer', 'GSTIN', 'Date', 'Due Date',
                'Type', 'Supply Type', 'Subtotal (₹)', 'CGST (₹)', 'SGST (₹)', 'IGST (₹)',
                'Discount (₹)', 'Total (₹)', 'Amount Paid (₹)', 'Balance Due (₹)', 'Status',
            ]);
            foreach ($invoices as $inv) {
                fputcsv($handle, [
                    $inv->invoice_number,
                    $inv->customer?->name,
                    $inv->customer?->gstin ?? '',
                    $inv->invoice_date?->format('d/m/Y'),
                    $inv->due_date?->format('d/m/Y') ?? '',
                    strtoupper($inv->invoice_type),
                    ucfirst($inv->supply_type),
                    number_format($inv->subtotal, 2, '.', ''),
                    number_format($inv->cgst_amount, 2, '.', ''),
                    number_format($inv->sgst_amount, 2, '.', ''),
                    number_format($inv->igst_amount, 2, '.', ''),
                    number_format($inv->discount_amount ?? 0, 2, '.', ''),
                    number_format($inv->total_amount, 2, '.', ''),
                    number_format($inv->amount_paid, 2, '.', ''),
                    number_format($inv->balance_due, 2, '.', ''),
                    strtoupper($inv->payment_status),
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function generateIRN(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);

        if ($invoice->irn) {
            return back()->withErrors(['invoice' => 'IRN already generated for this invoice.']);
        }

        if (!$invoice->tenant->gstin) {
            return back()->withErrors(['invoice' => 'Your business GSTIN is required to generate an IRN.']);
        }

        try {
            $result = $this->einvoice->generateIRN($invoice);

            $invoice->update([
                'irn'            => $result['irn'],
                'ack_no'         => $result['ack_no'],
                'ack_date'       => $result['ack_date'],
                'signed_qr_code' => $result['signed_qr_code'],
                'irn_status'     => 'active',
            ]);

            return back()->with('success', 'IRN generated successfully: ' . $result['irn']);
        } catch (\Throwable $e) {
            Log::error('E-Invoice IRN generation failed: ' . $e->getMessage());
            return back()->withErrors(['invoice' => 'IRN generation failed: ' . $e->getMessage()]);
        }
    }

    public function cancelIRN(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);

        if (!$invoice->irn) {
            return back()->withErrors(['invoice' => 'No IRN found for this invoice.']);
        }

        if ($invoice->irn_status === 'cancelled') {
            return back()->withErrors(['invoice' => 'IRN is already cancelled.']);
        }

        $request->validate([
            'cancel_reason' => 'required|in:1,2,3,4',
        ]);

        try {
            $this->einvoice->cancelIRN($invoice, $request->cancel_reason);

            $invoice->update(['irn_status' => 'cancelled']);

            return back()->with('success', 'IRN cancelled successfully.');
        } catch (\Throwable $e) {
            Log::error('E-Invoice cancel failed: ' . $e->getMessage());
            return back()->withErrors(['invoice' => 'IRN cancellation failed: ' . $e->getMessage()]);
        }
    }

    public function bulkDownload(Request $request)
    {
        $request->validate(['ids' => 'required|array|min:1|max:50', 'ids.*' => 'integer']);

        $tenant   = $request->user()->tenant;
        $invoices = Invoice::with(['customer', 'items', 'tenant', 'payments'])
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $request->ids)
            ->get();

        abort_if($invoices->isEmpty(), 404);

        $zipName = 'invoices-' . now()->format('Y-m-d') . '.zip';
        $zip     = new \ZipArchive();
        $tmpPath = sys_get_temp_dir() . '/' . $zipName;

        if ($zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->withErrors(['bulk' => 'Failed to create ZIP file.']);
        }

        foreach ($invoices as $invoice) {
            $pdf  = $this->pdfService->generate($invoice);
            $name = $invoice->invoice_number . '.pdf';
            $zip->addFromString($name, $pdf->output());
        }

        $zip->close();

        return response()->download($tmpPath, $zipName, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    public function bulkReminder(Request $request)
    {
        $request->validate(['ids' => 'required|array|min:1|max:50', 'ids.*' => 'integer']);

        $tenant   = $request->user()->tenant;
        $invoices = Invoice::with(['customer', 'tenant', 'payments'])
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $request->ids)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->get();

        $sent = 0;
        foreach ($invoices as $invoice) {
            $result = $this->notifications->sendPaymentReminder($invoice);
            if ($result['email'] || $result['whatsapp']) {
                $sent++;
            }
        }

        return back()->with('success', "Reminders sent for {$sent} invoice(s).");
    }

    private function authorizeInvoice(Request $request, Invoice $invoice): void
    {
        abort_unless($invoice->tenant_id === $request->user()->tenant_id, 403);
    }
}
