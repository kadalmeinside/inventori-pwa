<?php

namespace App\Http\Controllers;

use App\Enums\TransferStatus;
use App\Http\Requests\StoreStockTransferRequest;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockTransferController extends Controller
{
    public function __construct(private readonly StockMovementService $service) {}

    /**
     * Display a paginated listing of stock transfers.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        $query = StockTransfer::with(['product', 'sourceWarehouse', 'destinationWarehouse', 'requester'])
            ->when($user->role->value === 'branch_admin', function ($q) use ($user) {
                // Branch admins can only see transfers related to their warehouse
                $q->where('source_warehouse_id', $user->warehouse_id)
                  ->orWhere('destination_warehouse_id', $user->warehouse_id);
            });
            
        $transfers = $query->latest('created_at')->paginate(15)->withQueryString();

        // Pass warehouse and product lists for the Initiate Transfer modal
        $warehouses = Warehouse::all();
        $products   = \App\Models\Product::all(['id', 'name', 'sku']);

        return Inertia::render('Transfers/Index', [
            'transfers'  => $transfers,
            'warehouses' => $warehouses,
            'products'   => $products,
        ]);
    }

    /**
     * Initiate a stock transfer from source warehouse.
     */
    public function store(StoreStockTransferRequest $request): RedirectResponse
    {
        $source      = Warehouse::findOrFail($request->source_warehouse_id);
        $destination = Warehouse::findOrFail($request->destination_warehouse_id);

        // Policy: can only initiate FROM your own warehouse
        $this->authorize('create', [StockTransfer::class, $source]);

        try {
            $this->service->initiateTransfer(
                source:      $source,
                destination: $destination,
                product:     \App\Models\Product::findOrFail($request->product_id),
                quantity:    $request->quantity,
                requester:   $request->user(),
                notes:       $request->notes,
            );

            return redirect()->back()->with('success', 'Transfer initiated. Stock is now in transit.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Receive a transfer at the destination warehouse.
     */
    public function receive(Request $request, StockTransfer $stockTransfer): RedirectResponse
    {
        // Policy: can only receive INTO your own warehouse
        $this->authorize('receive', $stockTransfer);

        try {
            $this->service->receiveTransfer($stockTransfer, $request->user());
            return redirect()->back()->with('success', 'Transfer received. Destination stock updated.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
