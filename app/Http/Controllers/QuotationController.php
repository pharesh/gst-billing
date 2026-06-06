<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Services\GSTCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QuotationController extends Controller
{
    public function __construct(private GSTCalculationService $gst) {}

    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;

        $quotations = Quotation::with('customer')
            ->where('tenant_id', $tenant->id)
            ->when($request->search, fn ($q) => $q->where('quotation_number', 'like', "%{$request->search}%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$request->search}%")))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest('quotation_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Quotations/Index', [
            'quotations' => $quotations,
            'filters'    => $request->only(['search', 'status']),
        ]);
    }

    public function create(Request $request)
    {
        $tenant = $request->user()->tenant;

        return Inertia::render('Quotations/Create', [
            'customers' => Customer::where('tenant_id', $tenant->id)->get(['id', 'name', 'gstin', 'state_code', 'customer_type']),
            'products'  => Product::where('tenant_id', $tenant->id)->active()->get(['id', 'name', 'price', 'gst_rate', 'unit', 'hsn_sac_code']),
            'tenant'    => $tenant->only(['state_code', 'gstin', 'invoice_prefix']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'                => 'required|exists:customers,id',
            'quotation_date'             => 'required|date',
            'valid_until'                => 'nullable|date|after_or_equal:quotation_date',
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

        $tenant = $request->user()->tenant;

        DB::transaction(function () use ($validated, $tenant) {
            $quotationNumber = $tenant->nextQuotationNumber();

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

            $quotation = Quotation::create([
                'tenant_id'        => $tenant->id,
                'customer_id'      => $validated['customer_id'],
                'quotation_number' => $quotationNumber,
                'quotation_date'   => $validated['quotation_date'],
                'valid_until'      => $validated['valid_until'] ?? null,
                'invoice_type'     => $validated['invoice_type'],
                'supply_type'      => $validated['supply_type'],
                'notes'            => $validated['notes'] ?? null,
                'terms'            => $validated['terms'] ?? null,
                'status'           => 'draft',
                ...$totals,
            ]);

            foreach ($calculatedItems as $item) {
                QuotationItem::create(array_merge(['quotation_id' => $quotation->id], $item));
            }
        });

        return redirect()->route('quotations.index')->with('success', 'Quotation created successfully.');
    }

    public function show(Request $request, Quotation $quotation)
    {
        $this->authorize($request, $quotation);
        $quotation->load(['customer', 'items.product', 'tenant']);

        return Inertia::render('Quotations/Show', [
            'quotation'     => $quotation,
            'gstGroups'     => $this->gst->groupByGSTRate($quotation->items->map(fn ($i) => $i->toArray())->toArray()),
            'amountInWords' => $this->gst->amountInWords($quotation->total_amount),
        ]);
    }

    public function updateStatus(Request $request, Quotation $quotation)
    {
        $this->authorize($request, $quotation);

        $request->validate(['status' => 'required|in:draft,sent,accepted,rejected']);
        $quotation->update(['status' => $request->status]);

        return back()->with('success', 'Quotation status updated.');
    }

    public function convert(Request $request, Quotation $quotation)
    {
        $this->authorize($request, $quotation);

        if ($quotation->status === 'converted') {
            return back()->withErrors(['quotation' => 'Already converted to invoice.']);
        }

        $tenant = $request->user()->tenant;

        $invoice = DB::transaction(function () use ($quotation, $tenant) {
            $invoiceNumber = $tenant->nextInvoiceNumber();

            $invoice = Invoice::create([
                'tenant_id'      => $tenant->id,
                'customer_id'    => $quotation->customer_id,
                'invoice_number' => $invoiceNumber,
                'invoice_date'   => now()->toDateString(),
                'due_date'       => null,
                'invoice_type'   => $quotation->invoice_type,
                'supply_type'    => $quotation->supply_type,
                'notes'          => $quotation->notes,
                'terms'          => $quotation->terms,
                'subtotal'       => $quotation->subtotal,
                'cgst_amount'    => $quotation->cgst_amount,
                'sgst_amount'    => $quotation->sgst_amount,
                'igst_amount'    => $quotation->igst_amount,
                'discount_amount'=> $quotation->discount_amount,
                'total_amount'   => $quotation->total_amount,
                'amount_paid'    => 0,
                'payment_status' => 'unpaid',
            ]);

            foreach ($quotation->items as $item) {
                InvoiceItem::create([
                    'invoice_id'     => $invoice->id,
                    'product_id'     => $item->product_id,
                    'description'    => $item->description,
                    'hsn_sac_code'   => $item->hsn_sac_code,
                    'unit'           => $item->unit,
                    'quantity'       => $item->quantity,
                    'price'          => $item->price,
                    'discount_percent' => $item->discount_percent,
                    'taxable_amount' => $item->taxable_amount,
                    'gst_rate'       => $item->gst_rate,
                    'cgst_rate'      => $item->cgst_rate,
                    'sgst_rate'      => $item->sgst_rate,
                    'igst_rate'      => $item->igst_rate,
                    'cgst_amount'    => $item->cgst_amount,
                    'sgst_amount'    => $item->sgst_amount,
                    'igst_amount'    => $item->igst_amount,
                    'total_amount'   => $item->total_amount,
                    'sort_order'     => $item->sort_order,
                ]);
            }

            $quotation->update([
                'status'               => 'converted',
                'converted_invoice_id' => $invoice->id,
            ]);

            $tenant->incrementMonthlyInvoiceCount();

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice->id)
            ->with('success', 'Quotation converted to Invoice ' . $invoice->invoice_number . '.');
    }

    public function download(Request $request, Quotation $quotation)
    {
        $this->authorize($request, $quotation);
        $quotation->load(['tenant', 'customer', 'items.product']);

        $amountInWords = $this->gst->amountInWords($quotation->total_amount);

        $pdf = Pdf::loadView('quotations.pdf', compact('quotation', 'amountInWords'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('Quotation-' . $quotation->quotation_number . '.pdf');
    }

    public function destroy(Request $request, Quotation $quotation)
    {
        $this->authorize($request, $quotation);
        $quotation->delete();
        return redirect()->route('quotations.index')->with('success', 'Quotation deleted.');
    }

    private function authorize(Request $request, Quotation $quotation): void
    {
        abort_unless($quotation->tenant_id === $request->user()->tenant_id, 403);
    }
}
