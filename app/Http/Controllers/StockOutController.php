<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockOutRequest;
use App\Models\StockEntry;
use App\Models\StockOut;
use App\Models\Warehouse;
use App\Services\PushNotificationService;
use App\Services\StockMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockOutController extends Controller
{
    public function __construct(
        private readonly StockMovementService    $service,
        private readonly PushNotificationService $push,
    ) {}

    /**
     * Display a listing of stock out history.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = StockOut::with(['product', 'warehouse', 'requester'])
            ->visibleToUser($user);

        $stockOuts = $query->latest('created_at')->paginate(15);

        $warehouses = Warehouse::query()
            ->visibleToUser($user)
            ->orderedByName()
            ->get(['id', 'name']);

        $products = \App\Models\Product::query()
            ->active()
            ->orderedByName()
            ->get(['id', 'name', 'sku', 'unit']);

        $stocks = StockEntry::query()
            ->visibleToUser($user)
            ->get(['warehouse_id', 'product_id', 'quantity']);

        return Inertia::render('StockOuts/Index', [
            'stockOuts'  => $stockOuts,
            'warehouses' => $warehouses,
            'products'   => $products,
            'stocks'     => $stocks,
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

        try {
            $this->service->instantStockOut(
                warehouse:   $warehouse,
                product:     \App\Models\Product::findOrFail($request->product_id),
                quantity:    $request->quantity,
                performedBy: $user,
                category:    \App\Enums\StockOutCategory::from($request->category),
                reason:      $request->reason,
            );

            // Notify Super Admin instantly (Pusher toast)
            \App\Events\SystemNotification::dispatch(
                'superadmin',
                "Cabang {$warehouse->name} baru saja mencatat Stock Out sebesar {$request->quantity} item.",
                'info'
            );

            // ─── Push Notification ke Super Admin (background push) ────────────
            $productName = \App\Models\Product::find($request->product_id)?->name ?? 'Produk';
            $this->push->sendToSuperAdmins([
                'title' => "📄 Stock Out Baru",
                'body'  => "Cabang {$warehouse->name}: {$productName} \u00d7{$request->quantity} dicatat keluar.",
                'url'   => '/stock-outs',
                'tag'   => 'stock-out-new',
            ]);

            return redirect()->back()->with('success', 'Stock Out recorded successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
