<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\RecurringInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RecurringInvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $recurring = RecurringInvoice::with('customer')
            ->where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->paginate(20);

        return response()->json(['recurringInvoices' => $recurring]);
    }

    public function create(Request $request): JsonResponse
    {
        return response()->json([
            'customers' => Customer::where('tenant_id', $request->user()->tenant_id)
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id'          => 'required|exists:customers,id',
            'title'                => 'required|string|max:200',
            'frequency'            => 'required|in:weekly,monthly,quarterly,yearly',
            'start_date'           => 'required|date',
            'end_date'             => 'nullable|date|after:start_date',
            'invoice_type'         => 'required|in:b2b,b2c,export',
            'supply_type'          => 'required|in:intrastate,interstate',
            'due_days'             => 'required|integer|min:0|max:365',
            'notes'                => 'nullable|string|max:500',
            'terms'                => 'nullable|string|max:500',
            'items'                => 'required|array|min:1',
            'items.*.description'  => 'required|string',
            'items.*.hsn_sac_code' => 'nullable|string',
            'items.*.unit'         => 'required|string',
            'items.*.quantity'     => 'required|numeric|min:0.001',
            'items.*.price'        => 'required|numeric|min:0',
            'items.*.gst_rate'     => 'required|numeric|min:0|max:28',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $recurring = RecurringInvoice::create([
            ...$validated,
            'tenant_id'     => $request->user()->tenant_id,
            'next_run_date' => $validated['start_date'],
            'is_active'     => true,
        ]);

        return response()->json(['message' => 'Recurring invoice schedule created.', 'recurringInvoice' => $recurring], 201);
    }

    public function destroy(Request $request, RecurringInvoice $recurringInvoice): Response
    {
        abort_unless($recurringInvoice->tenant_id === $request->user()->tenant_id, 403);
        $recurringInvoice->delete();
        return response()->noContent();
    }

    public function toggle(Request $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        abort_unless($recurringInvoice->tenant_id === $request->user()->tenant_id, 403);
        $recurringInvoice->update(['is_active' => ! $recurringInvoice->is_active]);
        $msg = $recurringInvoice->is_active ? 'Schedule activated.' : 'Schedule paused.';
        return response()->json(['message' => $msg, 'recurringInvoice' => $recurringInvoice]);
    }
}
