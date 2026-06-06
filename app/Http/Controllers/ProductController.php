<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::where('tenant_id', $request->user()->tenant_id)
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'hsn_sac_code' => 'nullable|string|max:20',
            'type' => 'required|in:goods,service',
            'unit' => 'required|string|max:20',
            'price' => 'required|numeric|min:0',
            'gst_rate' => 'required|numeric|in:0,0.1,0.25,1,1.5,3,5,6,7.5,12,18,28',
        ]);

        Product::create([...$validated, 'tenant_id' => $request->user()->tenant_id, 'is_active' => true]);

        return back()->with('success', 'Product added.');
    }

    public function update(Request $request, Product $product)
    {
        abort_unless($product->tenant_id === $request->user()->tenant_id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'hsn_sac_code' => 'nullable|string|max:20',
            'type' => 'required|in:goods,service',
            'unit' => 'required|string|max:20',
            'price' => 'required|numeric|min:0',
            'gst_rate' => 'required|numeric|in:0,0.1,0.25,1,1.5,3,5,6,7.5,12,18,28',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Request $request, Product $product)
    {
        abort_unless($product->tenant_id === $request->user()->tenant_id, 403);
        $product->delete();
        return back()->with('success', 'Product deleted.');
    }
}
