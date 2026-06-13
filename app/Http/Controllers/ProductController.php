<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::where('tenant_id', $request->user()->tenant_id)
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'products' => $products,
            'filters'  => $request->only('search'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'hsn_sac_code' => 'nullable|string|max:20',
            'type'         => 'required|in:goods,service',
            'unit'         => 'required|string|max:20',
            'price'        => 'required|numeric|min:0',
            'gst_rate'     => 'required|numeric|in:0,0.1,0.25,1,1.5,3,5,6,7.5,12,18,28',
        ]);

        $product = Product::create([...$validated, 'tenant_id' => $request->user()->tenant_id, 'is_active' => true]);

        return response()->json(['message' => 'Product added.', 'product' => $product], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->tenant_id === $request->user()->tenant_id, 403);

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'hsn_sac_code' => 'nullable|string|max:20',
            'type'         => 'required|in:goods,service',
            'unit'         => 'required|string|max:20',
            'price'        => 'required|numeric|min:0',
            'gst_rate'     => 'required|numeric|in:0,0.1,0.25,1,1.5,3,5,6,7.5,12,18,28',
            'is_active'    => 'boolean',
        ]);

        $product->update($validated);

        return response()->json(['message' => 'Product updated.', 'product' => $product]);
    }

    public function destroy(Request $request, Product $product): Response
    {
        abort_unless($product->tenant_id === $request->user()->tenant_id, 403);
        $product->delete();
        return response()->noContent();
    }
}
