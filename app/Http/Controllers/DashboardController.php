<?php

namespace App\Http\Controllers;

use App\Models\StockEntry;
use App\Models\StockOut;
use App\Models\StockTransfer;
use App\Enums\TransferStatus;
use App\Enums\StockOutStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        // ─── Scope queries by warehouse ───────────────────────────────────
        $warehouseScope = fn ($q) => $user->isSuperAdmin()
            ? $q
            : $q->where('warehouse_id', $user->warehouse_id);

        // ─── Stock Alerts (below min_stock) ───────────────────────────────
        $stockAlerts = StockEntry::with(['product', 'warehouse'])
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->whereColumn('quantity', '<', 'products.min_stock')  // sub-select
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('warehouse_id', $user->warehouse_id))
            ->join('products', 'stock_entries.product_id', '=', 'products.id')
            ->select('stock_entries.*')
            ->get();

        // ─── KPI Stats ────────────────────────────────────────────────────
        $totalProducts = StockEntry::when(
                !$user->isSuperAdmin(),
                fn ($q) => $q->where('warehouse_id', $user->warehouse_id)
            )->count();

        $inTransit = StockTransfer::where('status', TransferStatus::InTransit->value)
            ->when(
                !$user->isSuperAdmin(),
                fn ($q) => $q
                    ->where('source_warehouse_id', $user->warehouse_id)
                    ->orWhere('destination_warehouse_id', $user->warehouse_id)
            )->sum('quantity');

        $pendingApprovals = StockOut::where('status', StockOutStatus::Pending->value)
            ->when(
                !$user->isSuperAdmin(),
                fn ($q) => $q->where('warehouse_id', $user->warehouse_id)
            )->count();

        return Inertia::render('Dashboard', [
            'stockAlerts' => $stockAlerts,
            'stats'       => [
                'totalProducts'    => $totalProducts,
                'inTransit'        => (int) $inTransit,
                'pendingApprovals' => $pendingApprovals,
            ],
        ]);
    }
}
