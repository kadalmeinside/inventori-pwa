<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockOutRequest;
use App\Models\StockOut;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockOutController extends Controller
{
    public function __construct(private readonly StockMovementService $service) {}

    /**
     * Display a listing of stock out history.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = StockOut::with(['product', 'warehouse', 'requester'])
            ->when($user->role->value === 'branch_admin', function ($q) use ($user) {
                $q->where('warehouse_id', $user->warehouse_id);
            });

        $stockOuts = $query->latest('created_at')->paginate(15);

        return Inertia::render('StockOuts/Index', [
            'stockOuts'  => $stockOuts,
            'warehouses' => Warehouse::all(),
            'products'   => \App\Models\Product::all(['id', 'name', 'sku', 'unit']),
        ]);
    }

    /**
     * Instantly deduct stock (no approval workflow).
     * Branch Admin can only deduct from their own warehouse.
     */
    public function store(StoreStockOutRequest $request): RedirectResponse
    {
        $user      = $request->user();
        $warehouse = Warehouse::findOrFail($request->warehouse_id);

        // Branch Admin can only stock out from their own warehouse
        if ($user->role->value === 'branch_admin' && $user->warehouse_id !== $warehouse->id) {
            abort(403, 'You can only manage stock in your own warehouse.');
        }

        try {
            $this->service->instantStockOut(
                warehouse:   $warehouse,
                product:     \App\Models\Product::findOrFail($request->product_id),
                quantity:    $request->quantity,
                performedBy: $user,
                category:    \App\Enums\StockOutCategory::from($request->category),
                reason:      $request->reason,
            );

            return redirect()->back()->with('success', 'Stock Out recorded successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
