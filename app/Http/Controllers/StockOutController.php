<?php

namespace App\Http\Controllers;

use App\Enums\StockOutStatus;
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
     * Display a listing of stock out requests.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = StockOut::with(['product', 'warehouse', 'requester', 'approver'])
            ->when($user->role->value === 'branch_admin', function ($q) use ($user) {
                // Branch admins only see their own warehouse requests
                $q->where('warehouse_id', $user->warehouse_id);
            });

        $stockOuts = $query->latest('created_at')->paginate(15);

        return Inertia::render('StockOuts/Index', [
            'stockOuts' => $stockOuts,
        ]);
    }

    /**
     * Store a new Stock Out request (pending).
     */
    public function store(StoreStockOutRequest $request): RedirectResponse
    {
        $warehouse = Warehouse::findOrFail($request->warehouse_id);

        // Policy: Branch Admin can only submit for their own warehouse
        $this->authorize('view', $warehouse);

        $this->service->requestStockOut(
            warehouse: $warehouse,
            product:   \App\Models\Product::findOrFail($request->product_id),
            quantity:  $request->quantity,
            requester: $request->user(),
            category:  \App\Enums\StockOutCategory::from($request->category),
            reason:    $request->reason,
        );

        return redirect()->back()->with('success', 'Stock Out request submitted for approval.');
    }

    /**
     * Approve a pending Stock Out.
     * Only Super Admin or designated approver.
     */
    public function approve(Request $request, StockOut $stockOut): RedirectResponse
    {
        $this->authorize('update', Warehouse::findOrFail($stockOut->warehouse_id));

        try {
            $this->service->approveStockOut($stockOut, $request->user());
            return redirect()->back()->with('success', 'Stock Out approved and ledger updated.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject a pending Stock Out.
     */
    public function reject(Request $request, StockOut $stockOut): RedirectResponse
    {
        $this->authorize('update', Warehouse::findOrFail($stockOut->warehouse_id));

        $this->service->rejectStockOut($stockOut, $request->user());

        return redirect()->back()->with('success', 'Stock Out request rejected.');
    }
}
