<?php

namespace App\Http\Controllers;

use App\Models\StockEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    /**
     * Display a paginated listing of stock entries.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        $query = StockEntry::with(['product', 'warehouse'])
            ->when($user->role->value === 'branch_admin', function ($q) use ($user) {
                // Branch Admin data isolation
                $q->where('warehouse_id', $user->warehouse_id);
            });
            
        // Optional Search Filtering
        if ($search = $request->input('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }
        
        $stocks = $query->orderBy('warehouse_id')->paginate(15)->withQueryString();

        return Inertia::render('Stocks/Index', [
            'stocks' => $stocks,
            'filters' => $request->only('search'),
            'warehouses' => \App\Models\Warehouse::all(),
            'products' => \App\Models\Product::all(['id', 'name', 'sku']),
        ]);
    }

    /**
     * Store a Stock In request (receive inventory).
     */
    public function store(Request $request, \App\Services\StockMovementService $service)
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'product_id'   => ['required', 'integer', 'exists:products,id'],
            'quantity'     => ['required', 'integer', 'min:1'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $warehouse = \App\Models\Warehouse::findOrFail($validated['warehouse_id']);
        
        // Ensure user belongs to the warehouse they are adding stock to
        if (!$request->user()->belongsToWarehouse($warehouse)) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $service->stockIn(
                warehouse: $warehouse,
                product: \App\Models\Product::findOrFail($validated['product_id']),
                quantity: $validated['quantity'],
                performedBy: $request->user(),
                notes: $validated['notes']
            );

            return redirect()->back()->with('success', 'Stock Received Successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
