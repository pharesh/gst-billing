<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\RecurringInvoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RecurringInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;

        $recurring = RecurringInvoice::with('customer')
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->paginate(20);

        return Inertia::render('RecurringInvoices/Index', [
            'recurringInvoices' => $recurring,
        ]);
    }

    public function create(Request $request)
    {
        $tenant = $request->user()->tenant;

        return Inertia::render('RecurringInvoices/Create', [
            'customers' => Customer::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'         => 'required|exists:customers,id',
            'title'               => 'required|string|max:200',
            'frequency'           => 'required|in:weekly,monthly,quarterly,yearly',
            'start_date'          => 'required|date',
            'end_date'            => 'nullable|date|after:start_date',
            'invoice_type'        => 'required|in:b2b,b2c,export',
            'supply_type'         => 'required|in:intrastate,interstate',
            'due_days'            => 'required|integer|min:0|max:365',
            'notes'               => 'nullable|string|max:500',
            'terms'               => 'nullable|string|max:500',
            'items'               => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.hsn_sac_code'=> 'nullable|string',
            'items.*.unit'        => 'required|string',
            'items.*.quantity'    => 'required|numeric|min:0.001',
            'items.*.price'       => 'required|numeric|min:0',
            'items.*.gst_rate'    => 'required|numeric|min:0|max:28',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $tenant = $request->user()->tenant;

        RecurringInvoice::create([
            ...$validated,
            'tenant_id'     => $tenant->id,
            'next_run_date' => $validated['start_date'],
            'is_active'     => true,
        ]);

        return redirect()->route('recurring-invoices.index')->with('success', 'Recurring invoice schedule created.');
    }

    public function destroy(Request $request, RecurringInvoice $recurringInvoice)
    {
        abort_unless($recurringInvoice->tenant_id === $request->user()->tenant_id, 403);
        $recurringInvoice->delete();
        return back()->with('success', 'Recurring schedule deleted.');
    }

    public function toggle(Request $request, RecurringInvoice $recurringInvoice)
    {
        abort_unless($recurringInvoice->tenant_id === $request->user()->tenant_id, 403);
        $recurringInvoice->update(['is_active' => !$recurringInvoice->is_active]);
        return back()->with('success', $recurringInvoice->is_active ? 'Schedule activated.' : 'Schedule paused.');
    }
}
