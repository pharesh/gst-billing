<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Services\GSTCalculationService;
use App\Services\InvoicePDFService;
use App\Services\NotificationService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function __construct(
        private GSTCalculationService $gst,
        private InvoicePDFService $pdfService,
        private WhatsAppService $whatsapp,
        private NotificationService $notifications
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
            ->when($request->date_from,   fn ($q) => $q->whereDate('invoice_date', '>=', $request->date_from))
            ->when($request->date_to,     fn ($q) => $q->whereDate('invoice_date', '<=', $request->date_to))
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
                                ->whereNotNull('due_date')->whereDate('due_date', '<', today())->count(),
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
            'invoice' => $invoice,
            'gstGroups' => $this->gst->groupByGSTRate($invoice->items->map(fn ($i) => $i->toArray())->toArray()),
            'amountInWords' => $this->gst->amountInWords($invoice->total_amount),
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

    private function authorizeInvoice(Request $request, Invoice $invoice): void
    {
        abort_unless($invoice->tenant_id === $request->user()->tenant_id, 403);
    }
}
