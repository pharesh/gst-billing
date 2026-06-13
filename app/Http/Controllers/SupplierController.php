<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $suppliers = Supplier::where('tenant_id', $request->user()->tenant_id)
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('gstin', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'suppliers' => $suppliers,
            'filters'   => $request->only('search'),
        ]);
    }

    public function store(Request $request): JsonResponse
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

        $supplier = Supplier::create([...$validated, 'tenant_id' => $request->user()->tenant_id]);

        return response()->json(['message' => 'Supplier added.', 'supplier' => $supplier], 201);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
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

        return response()->json(['message' => 'Supplier updated.', 'supplier' => $supplier]);
    }

    public function destroy(Request $request, Supplier $supplier): Response
    {
        abort_unless($supplier->tenant_id === $request->user()->tenant_id, 403);
        $supplier->delete();
        return response()->noContent();
    }
}
