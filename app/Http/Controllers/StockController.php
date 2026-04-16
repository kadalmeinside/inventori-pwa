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
        $isGlobal = $request->input('view_mode') === 'global' && $user->role->value === 'super_admin';
        $warehouseId = $request->input('warehouse_id');
        
        $query = StockEntry::with(['product.category', 'warehouse'])
            ->when($user->role->value === 'branch_admin', function ($q) use ($user) {
                // Branch Admin data isolation
                $q->where('warehouse_id', $user->warehouse_id);
            });
            
        // Super Admin Filter
        if ($user->role->value === 'super_admin' && !$isGlobal && $warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
            
        // Optional Search Filtering
        if ($search = $request->input('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }
        
        if ($isGlobal) {
            $query->selectRaw('MIN(id) as id, product_id, SUM(quantity) as quantity')
                  ->groupBy('product_id');
            $stocks = $query->paginate(15)->withQueryString();
        } else {
            $stocks = $query->orderBy('warehouse_id')->paginate(15)->withQueryString();
        }

        return Inertia::render('Stocks/Index', [
            'stocks' => $stocks,
            'filters' => $request->only('search', 'warehouse_id', 'view_mode'),
            'warehouses' => \App\Models\Warehouse::all(),
            'products' => \App\Models\Product::with('category')->get(['id', 'name', 'sku', 'category_id']),
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

            // Notify Super Admin if Branch Admin adds stock directly
            if ($request->user()->role->value === 'branch_admin') {
                \App\Events\SystemNotification::dispatch(
                    'superadmin',
                    "Cabang {$warehouse->name} baru saja melakukan Menerima Stok sebesar {$validated['quantity']} item.",
                    'success'
                );
            }

            return redirect()->back()->with('success', 'Stock Received Successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
