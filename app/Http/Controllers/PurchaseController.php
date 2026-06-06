<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Services\GSTCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PurchaseController extends Controller
{
    public function __construct(private GSTCalculationService $gst) {}

    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;

        $bills = PurchaseInvoice::with('supplier')
            ->where('tenant_id', $tenant->id)
            ->when($request->search, fn ($q) => $q->where('bill_number', 'like', "%{$request->search}%")
                ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$request->search}%")))
            ->when($request->status,    fn ($q) => $q->where('payment_status', $request->status))
            ->when($request->date_from, fn ($q) => $q->where('bill_date', '>=', $request->date_from))
            ->when($request->date_to,   fn ($q) => $q->where('bill_date', '<=', $request->date_to))
            ->latest('bill_date')
            ->paginate(20)
            ->withQueryString();

        $base = PurchaseInvoice::where('tenant_id', $tenant->id);

        // ITC summary for current tenant
        $itcSummary = [
            'total_purchases'  => (clone $base)->sum('total_amount'),
            'itc_cgst'         => (clone $base)->where('itc_eligible', true)->sum('cgst_amount'),
            'itc_sgst'         => (clone $base)->where('itc_eligible', true)->sum('sgst_amount'),
            'itc_igst'         => (clone $base)->where('itc_eligible', true)->sum('igst_amount'),
            'outstanding'      => (clone $base)->whereIn('payment_status', ['unpaid', 'partial'])
                ->get(['total_amount', 'amount_paid'])
                ->sum(fn ($b) => $b->total_amount - $b->amount_paid),
        ];
        $itcSummary['itc_total'] = $itcSummary['itc_cgst'] + $itcSummary['itc_sgst'] + $itcSummary['itc_igst'];

        return Inertia::render('Purchases/Index', [
            'bills'      => $bills,
            'itcSummary' => $itcSummary,
            'filters'    => $request->only(['search', 'status', 'date_from', 'date_to']),
        ]);
    }

    public function create(Request $request)
    {
        $tenant = $request->user()->tenant;

        return Inertia::render('Purchases/Create', [
            'suppliers' => Supplier::where('tenant_id', $tenant->id)->active()->get(['id', 'name', 'gstin', 'state_code']),
            'tenant'    => $tenant->only(['state_code', 'gstin', 'invoice_prefix']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'                => 'required|exists:suppliers,id',
            'bill_number'                => 'nullable|string|max:100',
            'bill_date'                  => 'required|date',
            'due_date'                   => 'nullable|date|after_or_equal:bill_date',
            'supply_type'                => 'required|in:intrastate,interstate',
            'itc_eligible'               => 'boolean',
            'notes'                      => 'nullable|string|max:500',
            'items'                      => 'required|array|min:1',
            'items.*.description'        => 'required|string',
            'items.*.hsn_sac_code'       => 'nullable|string',
            'items.*.unit'               => 'required|string',
            'items.*.quantity'           => 'required|numeric|min:0.001',
            'items.*.price'              => 'required|numeric|min:0',
            'items.*.gst_rate'           => 'required|numeric|min:0|max:28',
        ]);

        $tenant = $request->user()->tenant;

        DB::transaction(function () use ($validated, $tenant) {
            $billNumber = !empty($validated['bill_number'])
                ? $validated['bill_number']
                : $tenant->nextPurchaseBillNumber();

            $calculatedItems = collect($validated['items'])->map(function ($item, $index) use ($validated) {
                $calc = $this->gst->calculateItem(
                    $item['price'],
                    $item['quantity'],
                    $item['gst_rate'],
                    $validated['supply_type'],
                    0
                );
                return array_merge($item, $calc, ['sort_order' => $index]);
            });

            $totals = $this->gst->calculateInvoiceTotals($calculatedItems->toArray());

            $bill = PurchaseInvoice::create([
                'tenant_id'      => $tenant->id,
                'supplier_id'    => $validated['supplier_id'],
                'bill_number'    => $billNumber,
                'bill_date'      => $validated['bill_date'],
                'due_date'       => $validated['due_date'] ?? null,
                'supply_type'    => $validated['supply_type'],
                'itc_eligible'   => $validated['itc_eligible'] ?? true,
                'notes'          => $validated['notes'] ?? null,
                'amount_paid'    => 0,
                'payment_status' => 'unpaid',
                ...$totals,
            ]);

            foreach ($calculatedItems as $item) {
                PurchaseInvoiceItem::create(array_merge(['purchase_invoice_id' => $bill->id], $item));
            }
        });

        return redirect()->route('purchases.index')->with('success', 'Purchase bill recorded successfully.');
    }

    public function show(Request $request, PurchaseInvoice $purchase)
    {
        $this->authorize($request, $purchase);
        $purchase->load(['supplier', 'items', 'tenant']);

        return Inertia::render('Purchases/Show', [
            'purchase'      => $purchase,
            'gstGroups'     => $this->gst->groupByGSTRate($purchase->items->map(fn ($i) => $i->toArray())->toArray()),
            'amountInWords' => $this->gst->amountInWords($purchase->total_amount),
        ]);
    }

    public function markPaid(Request $request, PurchaseInvoice $purchase)
    {
        $this->authorize($request, $purchase);

        $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,upi,cheque,card,other',
            'reference'      => 'nullable|string|max:100',
        ]);

        $newPaid = $purchase->amount_paid + $request->amount;
        $status  = $newPaid >= $purchase->total_amount ? 'paid' : 'partial';

        $purchase->update([
            'amount_paid'    => min($newPaid, $purchase->total_amount),
            'payment_status' => $status,
        ]);

        return back()->with('success', 'Payment recorded.');
    }

    public function destroy(Request $request, PurchaseInvoice $purchase)
    {
        $this->authorize($request, $purchase);
        $purchase->delete();
        return redirect()->route('purchases.index')->with('success', 'Purchase bill deleted.');
    }

    private function authorize(Request $request, PurchaseInvoice $purchase): void
    {
        abort_unless($purchase->tenant_id === $request->user()->tenant_id, 403);
    }
}
