<?php

namespace App\Http\Controllers;

use App\Models\DebitNote;
use App\Models\DebitNoteItem;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Services\GSTCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DebitNoteController extends Controller
{
    public function __construct(private GSTCalculationService $gst) {}

    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;

        $notes = DebitNote::with(['supplier:id,name', 'purchaseInvoice:id,bill_number'])
            ->where('tenant_id', $tenant->id)
            ->when($request->search, fn ($q) => $q->where('debit_note_number', 'like', "%{$request->search}%"))
            ->when($request->supplier_id, fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->orderBy('debit_note_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('DebitNotes/Index', [
            'notes'     => $notes,
            'suppliers' => Supplier::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']),
            'filters'   => $request->only(['search', 'supplier_id']),
            'summary'   => [
                'total' => DebitNote::where('tenant_id', $tenant->id)->sum('total_amount'),
                'count' => DebitNote::where('tenant_id', $tenant->id)->count(),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $tenant = $request->user()->tenant;

        $purchaseId = $request->purchase_invoice_id;
        $purchase   = $purchaseId
            ? PurchaseInvoice::with(['supplier', 'items'])
                ->where('tenant_id', $tenant->id)
                ->findOrFail($purchaseId)
            : null;

        return Inertia::render('DebitNotes/Create', [
            'suppliers'       => Supplier::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name', 'gstin']),
            'prefillPurchase' => $purchase,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'          => 'required|integer',
            'purchase_invoice_id'  => 'nullable|integer',
            'debit_note_date'      => 'required|date',
            'supply_type'          => 'required|in:intrastate,interstate',
            'reason'               => 'nullable|string|max:200',
            'notes'                => 'nullable|string|max:500',
            'items'                => 'required|array|min:1',
            'items.*.description'  => 'required|string',
            'items.*.hsn_sac_code' => 'nullable|string',
            'items.*.unit'         => 'required|string',
            'items.*.quantity'     => 'required|numeric|min:0.001',
            'items.*.price'        => 'required|numeric|min:0',
            'items.*.gst_rate'     => 'required|numeric|min:0|max:28',
        ]);

        $tenant = $request->user()->tenant;

        DB::transaction(function () use ($validated, $tenant) {
            $number = $tenant->nextDebitNoteNumber();

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

            $dn = DebitNote::create([
                'tenant_id'           => $tenant->id,
                'supplier_id'         => $validated['supplier_id'],
                'purchase_invoice_id' => $validated['purchase_invoice_id'] ?? null,
                'debit_note_number'   => $number,
                'debit_note_date'     => $validated['debit_note_date'],
                'supply_type'         => $validated['supply_type'],
                'reason'              => $validated['reason'] ?? null,
                'notes'               => $validated['notes'] ?? null,
                ...$totals,
            ]);

            foreach ($calculatedItems as $item) {
                DebitNoteItem::create(array_merge(['debit_note_id' => $dn->id], $item));
            }
        });

        return redirect()->route('debit-notes.index')->with('success', 'Debit Note created.');
    }

    public function show(Request $request, DebitNote $debitNote)
    {
        abort_unless($debitNote->tenant_id === $request->user()->tenant_id, 403);

        $debitNote->load(['supplier', 'purchaseInvoice', 'items']);

        return Inertia::render('DebitNotes/Show', [
            'debitNote'     => $debitNote,
            'gstGroups'     => $this->gst->groupByGSTRate($debitNote->items->map(fn ($i) => $i->toArray())->toArray()),
            'amountInWords' => $this->gst->amountInWords($debitNote->total_amount),
        ]);
    }

    public function destroy(Request $request, DebitNote $debitNote)
    {
        abort_unless($debitNote->tenant_id === $request->user()->tenant_id, 403);

        $debitNote->items()->delete();
        $debitNote->delete();

        return redirect()->route('debit-notes.index')->with('success', 'Debit Note deleted.');
    }
}
