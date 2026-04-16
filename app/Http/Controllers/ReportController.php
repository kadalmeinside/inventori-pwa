<?php

namespace App\Http\Controllers;

use App\Models\InventoryLog;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // ─── Filter Defaults ──────────────────────────────────────────────────
        $period = $request->query('period', '30d');
        $from = null;
        $to = null;

        // Parse date filters
        if ($period === '7d') {
            $from = now()->subDays(7)->startOfDay();
            $to = now()->endOfDay();
        } elseif ($period === '30d') {
            $from = now()->subDays(30)->startOfDay();
            $to = now()->endOfDay();
        } elseif (preg_match('/^\d{4}-\d{2}$/', $period)) {
            // YYYY-MM
            try {
                $date = Carbon::createFromFormat('Y-m', $period);
                $from = $date->copy()->startOfMonth();
                $to = $date->copy()->endOfMonth();
            } catch (\Exception $e) {
                // fallback
                $from = now()->subDays(30)->startOfDay();
                $to = now()->endOfDay();
                $period = '30d';
            }
        } elseif ($period === 'custom') {
            $from = $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : now()->subDays(30)->startOfDay();
            $to = $request->query('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();
        } else {
            // fallback
            $from = now()->subDays(30)->startOfDay();
            $to = now()->endOfDay();
            $period = '30d';
        }

        // ─── Query Base ──────────────────────────────────────────────────────
        $warehouseId = $request->query('warehouse_id');
        
        // Branch admin is locked to their own warehouse
        if (!$user->isSuperAdmin()) {
            $warehouseId = $user->warehouse_id;
        }

        $query = InventoryLog::with(['product', 'warehouse', 'creator', 'reference'])
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereBetween('created_at', [$from, $to]);

        // Optional type filter
        if ($type = $request->query('type')) {
            $query->where('movement_type', $type);
        }

        // Optional product filter
        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        // ─── Metrics ────────────────────────────────────────────────────────
        $metricsQuery = InventoryLog::when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereBetween('created_at', [$from, $to]);

        $inTotal = (clone $metricsQuery)->whereIn('movement_type', ['stock_in', 'transfer_in'])->sum('quantity');
        $outTotal = (clone $metricsQuery)->whereIn('movement_type', ['stock_out', 'transfer_out'])->sum('quantity');
        
        $stockOutBreakdown = DB::table('stock_outs')
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereBetween('created_at', [$from, $to])
            ->select('category', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('category')
            ->get();

        // ─── Current Stock Analytics ───────────────────────────────────────
        $currentStocks = \App\Models\StockEntry::with(['product', 'warehouse'])
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->get();
        
        $lowStockCount = $currentStocks->filter(fn($entry) => $entry->quantity < $entry->product->min_stock)->count();

        // Available Months for dropdown (from inventory logs)
        $availableMonths = DB::table('inventory_logs')
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'))
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->pluck('month');
            
        // ─── Paginated Logs ───────────────────────────────────────────────
        $logs = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Reports/Index', [
            'logs' => $logs,
            'metrics' => [
                'inTotal' => (int) $inTotal,
                'outTotal' => (int) $outTotal,
                'net' => (int) ($inTotal - $outTotal),
                'stockOutBreakdown' => $stockOutBreakdown,
                'lowStockCount' => $lowStockCount,
                'currentStocks' => $currentStocks,
            ],
            'filters' => [
                'period' => $period,
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'warehouse_id' => $warehouseId,
                'type' => $request->query('type'),
                'product_id' => $productId,
            ],
            'availableMonths' => $availableMonths,
            'warehouses' => $user->isSuperAdmin() ? Warehouse::all(['id', 'name']) : [],
            'products' => \App\Models\Product::all(['id', 'name', 'sku']),
        ]);
    }
}
