<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role->value !== 'super_admin') abort(403);

        $products = Product::latest()->paginate(15);
        return Inertia::render('Products/Index', [
            'products' => $products
        ]);
    }

    public function store(Request $request)
    {
        if ($request->user()->role->value !== 'super_admin') abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:products,sku',
            'category' => 'nullable|string|max:255',
            'unit' => 'required|string|max:20',
            'min_stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        Product::create($validated);
        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function update(Request $request, Product $product)
    {
        if ($request->user()->role->value !== 'super_admin') abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => ['required', 'string', 'max:50', Rule::unique('products')->ignore($product)],
            'category' => 'nullable|string|max:255',
            'unit' => 'required|string|max:20',
            'min_stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $product->update($validated);
        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Request $request, Product $product)
    {
        if ($request->user()->role->value !== 'super_admin') abort(403);

        $product->delete(); // Soft delete via model trait
        return redirect()->route('products.index')->with('success', 'Product set to inactive (soft deleted).');
    }
}
