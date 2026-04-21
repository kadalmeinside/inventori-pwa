<?php

namespace App\Http\Controllers;

use App\Models\StockEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Gate;

class StockController extends Controller
{
    /**
     * Display a paginated listing of stock entries.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isGlobal = $request->input('view_mode') === 'global' && $user->isSuperAdmin();
        $warehouseId = $request->input('warehouse_id');
        
        $query = StockEntry::with(['product.category'])
            ->when($user->isBranchAdmin(), function ($q) use ($user) {
                // Branch Admin data isolation
                $q->where('warehouse_id', $user->warehouse_id);
            });
            
        if (!$isGlobal) {
            $query->with(['warehouse']);
        }
            
        // Super Admin Filter
        if ($user->isSuperAdmin() && !$isGlobal && $warehouseId) {
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

        $warehouses = \App\Models\Warehouse::query()
            ->visibleToUser($user)
            ->orderedByName()
            ->get(['id', 'name']);

        return Inertia::render('Stocks/Index', [
            'stocks' => $stocks,
            'filters' => $request->only('search', 'warehouse_id', 'view_mode'),
            'warehouses' => $warehouses,
            'products' => \App\Models\Product::query()
                ->with('category')
                ->active()
                ->orderedByName()
                ->get(['id', 'name', 'sku', 'category_id']),
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
        Gate::authorize('addStock', $warehouse);

        try {
            $service->stockIn(
                warehouse: $warehouse,
                product: \App\Models\Product::findOrFail($validated['product_id']),
                quantity: $validated['quantity'],
                performedBy: $request->user(),
                notes: $validated['notes']
            );

            // Notify Super Admin if Branch Admin adds stock directly
            if ($request->user()->isBranchAdmin()) {
                \App\Events\SystemNotification::dispatch(
                    'superadmin',
                    "Cabang {$warehouse->name} baru saja melakukan Menerima Stok sebesar {$validated['quantity']} item.",
                    'success'
                );

                // ─── Push Notification ke Super Admin ────────────────────────────
                $productName = \App\Models\Product::find($validated['product_id'])?->name ?? 'Produk';
                app(PushNotificationService::class)->sendToSuperAdmins([
                    'title' => "⬆️ Stock In Baru",
                    'body'  => "Cabang {$warehouse->name}: {$productName} \u00d7{$validated['quantity']} diterima.",
                    'url'   => '/stocks',
                    'tag'   => 'stock-in-new',
                ]);
            }

            return redirect()->back()->with('success', 'Stock Received Successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
