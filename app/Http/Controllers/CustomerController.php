<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::where('tenant_id', $request->user()->tenant_id)
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('gstin', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'customers' => $customers,
            'filters'   => $request->only('search'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'gstin'         => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'customer_type' => 'required|in:b2b,b2c',
            'address'       => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:100',
            'state_code'    => 'nullable|string|max:2',
            'pincode'       => 'nullable|string|max:10',
            'phone'         => 'nullable|string|max:15',
            'email'         => 'nullable|email|max:255',
        ]);

        $customer = Customer::create([...$validated, 'tenant_id' => $request->user()->tenant_id]);

        return response()->json(['message' => 'Customer added.', 'customer' => $customer], 201);
    }

    public function show(Request $request, Customer $customer): JsonResponse
    {
        abort_unless($customer->tenant_id === $request->user()->tenant_id, 403);

        return response()->json(['customer' => $customer]);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        abort_unless($customer->tenant_id === $request->user()->tenant_id, 403);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'gstin'         => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'customer_type' => 'required|in:b2b,b2c',
            'address'       => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:100',
            'state_code'    => 'nullable|string|max:2',
            'pincode'       => 'nullable|string|max:10',
            'phone'         => 'nullable|string|max:15',
            'email'         => 'nullable|email|max:255',
        ]);

        $customer->update($validated);

        return response()->json(['message' => 'Customer updated.', 'customer' => $customer]);
    }

    public function statement(Request $request, Customer $customer): JsonResponse
    {
        abort_unless($customer->tenant_id === $request->user()->tenant_id, 403);

        $invoices = Invoice::with('payments')
            ->where('customer_id', $customer->id)
            ->orderBy('invoice_date')
            ->get();

        $totalBilled  = $invoices->sum('total_amount');
        $totalPaid    = $invoices->sum('amount_paid');
        $totalBalance = $totalBilled - $totalPaid;

        return response()->json([
            'customer' => $customer,
            'invoices' => $invoices,
            'summary'  => [
                'total_billed'  => $totalBilled,
                'total_paid'    => $totalPaid,
                'total_balance' => $totalBalance,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $customers = Customer::where('tenant_id', $request->user()->tenant_id)
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('gstin', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->get();

        $filename = 'customers-'.now()->format('Y-m-d').'.csv';

        return response()->stream(function () use ($customers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'GSTIN', 'Type', 'Phone', 'Email', 'Address', 'City', 'State', 'State Code', 'Pincode']);
            foreach ($customers as $c) {
                fputcsv($handle, [
                    $c->name, $c->gstin ?? '', strtoupper($c->customer_type),
                    $c->phone ?? '', $c->email ?? '', $c->address ?? '',
                    $c->city ?? '', $c->state ?? '', $c->state_code ?? '', $c->pincode ?? '',
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function destroy(Request $request, Customer $customer): Response
    {
        abort_unless($customer->tenant_id === $request->user()->tenant_id, 403);
        $customer->delete();
        return response()->noContent();
    }
}
