<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        // Only Super Admin can manage Master Data natively at the controller level
        if ($request->user()->role->value !== 'super_admin') {
            abort(403, 'Unauthorized access.');
        }

        $warehouses = Warehouse::latest()->paginate(15);
        return Inertia::render('Warehouses/Index', [
            'warehouses' => $warehouses
        ]);
    }

    public function store(Request $request)
    {
        if ($request->user()->role->value !== 'super_admin') abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:warehouses,code',
            'address' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        Warehouse::create($validated);
        return redirect()->route('warehouses.index')->with('success', 'Warehouse created successfully.');
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        if ($request->user()->role->value !== 'super_admin') abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:20', Rule::unique('warehouses')->ignore($warehouse)],
            'address' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $warehouse->update($validated);
        return redirect()->route('warehouses.index')->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Request $request, Warehouse $warehouse)
    {
        if ($request->user()->role->value !== 'super_admin') abort(403);

        $warehouse->delete(); // Soft delete via model trait
        return redirect()->route('warehouses.index')->with('success', 'Warehouse deleted successfully.');
    }
}
