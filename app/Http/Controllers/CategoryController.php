<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // Only Super Admin can manage Categories
        if ($request->user()->role->value !== 'super_admin') {
            abort(403, 'Unauthorized access.');
        }

        $categories = Category::latest()->paginate(15);
        return Inertia::render('Categories/Index', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        if ($request->user()->role->value !== 'super_admin') abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Category::create($validated);
        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    public function update(Request $request, Category $category)
    {
        if ($request->user()->role->value !== 'super_admin') abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category->update($validated);
        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Request $request, Category $category)
    {
        if ($request->user()->role->value !== 'super_admin') abort(403);

        $category->delete(); // Or handle if products exist? Better handle foreign key error or check first.
        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }
}
