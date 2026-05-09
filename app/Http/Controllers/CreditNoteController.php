<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\GSTCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CreditNoteController extends Controller
{
    public function __construct(private GSTCalculationService $gst) {}

    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;

        $notes = CreditNote::with('customer', 'invoice')
            ->where('tenant_id', $tenant->id)
            ->when($request->search, fn ($q) => $q->where('credit_note_number', 'like', "%{$request->search}%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$request->search}%")))
            ->latest('credit_note_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('CreditNotes/Index', [
            'creditNotes' => $notes,
            'filters'     => $request->only('search'),
        ]);
    }

    public function create(Request $request)
    {
        $tenant = $request->user()->tenant;
        $invoiceId = $request->query('invoice_id');
        $invoice = null;

        if ($invoiceId) {
            $invoice = Invoice::with('items', 'customer')
                ->where('tenant_id', $tenant->id)
                ->find($invoiceId);
        }

        return Inertia::render('CreditNotes/Create', [
            'customers' => Customer::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name', 'gstin', 'state_code']),
            'invoice'   => $invoice,
            'tenant'    => $tenant->only(['state_code', 'gstin', 'invoice_prefix']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'         => 'required|exists:customers,id',
            'invoice_id'          => 'nullable|exists:invoices,id',
            'credit_note_date'    => 'required|date',
            'reason'              => 'required|string|max:500',
            'supply_type'         => 'required|in:intrastate,interstate',
            'notes'               => 'nullable|string|max:500',
            'items'               => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.hsn_sac_code'=> 'nullable|string',
            'items.*.unit'        => 'required|string',
            'items.*.quantity'    => 'required|numeric|min:0.001',
            'items.*.price'       => 'required|numeric|min:0',
            'items.*.gst_rate'    => 'required|numeric|min:0|max:28',
        ]);

        $tenant = $request->user()->tenant;

        abort_if(
            $validated['invoice_id'] && Invoice::find($validated['invoice_id'])?->tenant_id !== $tenant->id,
            403
        );

        DB::transaction(function () use ($validated, $tenant) {
            $number = $tenant->nextCreditNoteNumber();

            $calculatedItems = collect($validated['items'])->map(function ($item, $index) use ($validated) {
                $calc = $this->gst->calculateItem(
                    $item['price'],
                    $item['quantity'],
                    $item['gst_rate'],
                    $validated['supply_type']
                );
                return array_merge($item, [
                    'taxable_amount' => $calc['taxable_amount'],
                    'cgst_amount'    => $calc['cgst_amount'],
                    'sgst_amount'    => $calc['sgst_amount'],
                    'igst_amount'    => $calc['igst_amount'],
                    'total_amount'   => $calc['total_amount'],
                    'sort_order'     => $index,
                ]);
            });

            $totals = $this->gst->calculateInvoiceTotals($calculatedItems->toArray());

            $note = CreditNote::create([
                'tenant_id'          => $tenant->id,
                'customer_id'        => $validated['customer_id'],
                'invoice_id'         => $validated['invoice_id'] ?? null,
                'credit_note_number' => $number,
                'credit_note_date'   => $validated['credit_note_date'],
                'reason'             => $validated['reason'],
                'supply_type'        => $validated['supply_type'],
                'notes'              => $validated['notes'] ?? null,
                'subtotal'           => $totals['subtotal'],
                'cgst_amount'        => $totals['cgst_amount'],
                'sgst_amount'        => $totals['sgst_amount'],
                'igst_amount'        => $totals['igst_amount'],
                'total_amount'       => $totals['total_amount'],
                'status'             => 'issued',
            ]);

            foreach ($calculatedItems as $item) {
                CreditNoteItem::create(array_merge(['credit_note_id' => $note->id], $item));
            }
        });

        return redirect()->route('credit-notes.index')->with('success', 'Credit note issued successfully.');
    }

    public function show(Request $request, CreditNote $creditNote)
    {
        $this->authorize($request, $creditNote);
        $creditNote->load(['customer', 'items', 'invoice', 'tenant']);

        return Inertia::render('CreditNotes/Show', [
            'creditNote' => $creditNote,
            'gstGroups'  => $this->gst->groupByGSTRate($creditNote->items->map(fn ($i) => $i->toArray())->toArray()),
        ]);
    }

    public function destroy(Request $request, CreditNote $creditNote)
    {
        $this->authorize($request, $creditNote);
        $creditNote->delete();
        return redirect()->route('credit-notes.index')->with('success', 'Credit note deleted.');
    }

    private function authorize(Request $request, CreditNote $creditNote): void
    {
        abort_unless($creditNote->tenant_id === $request->user()->tenant_id, 403);
    }
}
