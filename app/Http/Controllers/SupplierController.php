<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::where('tenant_id', $request->user()->tenant_id)
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('gstin', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters'   => $request->only('search'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'gstin'         => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'supplier_type' => 'required|in:registered,unregistered',
            'address'       => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:100',
            'state_code'    => 'nullable|string|max:2',
            'pincode'       => 'nullable|string|max:10',
            'phone'         => 'nullable|string|max:15',
            'email'         => 'nullable|email|max:255',
            'payment_terms' => 'nullable|string|max:100',
            'is_active'     => 'boolean',
        ]);

        Supplier::create([...$validated, 'tenant_id' => $request->user()->tenant_id]);

        return back()->with('success', 'Supplier added.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        abort_unless($supplier->tenant_id === $request->user()->tenant_id, 403);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'gstin'         => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'supplier_type' => 'required|in:registered,unregistered',
            'address'       => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:100',
            'state_code'    => 'nullable|string|max:2',
            'pincode'       => 'nullable|string|max:10',
            'phone'         => 'nullable|string|max:15',
            'email'         => 'nullable|email|max:255',
            'payment_terms' => 'nullable|string|max:100',
            'is_active'     => 'boolean',
        ]);

        $supplier->update($validated);

        return back()->with('success', 'Supplier updated.');
    }

    public function destroy(Request $request, Supplier $supplier)
    {
        abort_unless($supplier->tenant_id === $request->user()->tenant_id, 403);
        $supplier->delete();

        return back()->with('success', 'Supplier deleted.');
    }
}
