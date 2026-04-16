<?php

namespace App\Http\Controllers;

use App\Models\StockEntry;
use App\Models\TransferRequest;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransferRequestController extends Controller
{
    public function __construct(private readonly StockMovementService $service) {}

    /**
     * List all transfer requests.
     * Branch Admin sees only their own; Super Admin sees all.
     */
    public function index(Request $request): Response
    {
        $user  = $request->user();
        $query = TransferRequest::with(['requester', 'fromWarehouse', 'toWarehouse', 'product', 'reviewer']);

        if ($user->role->value === 'branch_admin') {
            $query->where('to_warehouse_id', $user->warehouse_id);
        }

        $requests = $query->latest()->paginate(15);

        return Inertia::render('TransferRequests/Index', [
            'requests'   => $requests,
            'warehouses' => Warehouse::all(),
            'products'   => \App\Models\Product::all(['id', 'name', 'sku', 'unit']),
            'stocks'     => StockEntry::all(['warehouse_id', 'product_id', 'quantity']),
        ]);
    }

    /**
     * Branch Admin creates a transfer request.
     * They specify destination (auto = their warehouse) and desired product/quantity.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role->value !== 'branch_admin') {
            abort(403, 'Only Branch Admins can create transfer requests.');
        }

        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1'],
            'notes'      => ['nullable', 'string', 'max:500'],
        ]);

        TransferRequest::create([
            'requester_id'     => $user->id,
            'from_warehouse_id'=> null, // Super admin will decide source when approving
            'to_warehouse_id'  => $user->warehouse_id,
            'product_id'       => $data['product_id'],
            'quantity'         => $data['quantity'],
            'notes'            => $data['notes'] ?? null,
            'status'           => 'pending',
        ]);

        \App\Events\SystemNotification::dispatch(
            'superadmin',
            "Cabang " . $user->warehouse->name . " mengajukan Request Transfer baru.",
            'info'
        );

        return redirect()->back()->with('success', 'Transfer request submitted. Awaiting approval.');
    }

    /**
     * Super Admin approves the request and initiates the actual transfer.
     */
    public function approve(Request $request, TransferRequest $transferRequest): RedirectResponse
    {
        if ($request->user()->role->value !== 'super_admin') {
            abort(403, 'Only Super Admin can approve transfer requests.');
        }

        if (! $transferRequest->isPending()) {
            return redirect()->back()->with('error', 'This request has already been reviewed.');
        }

        $data = $request->validate([
            'from_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
        ]);

        $source      = Warehouse::findOrFail($data['from_warehouse_id']);
        $destination = Warehouse::findOrFail($transferRequest->to_warehouse_id);

        if ($source->id === $destination->id) {
            return redirect()->back()->with('error', 'Source and destination cannot be the same warehouse.');
        }

        try {
            $this->service->initiateTransfer(
                source:      $source,
                destination: $destination,
                product:     \App\Models\Product::findOrFail($transferRequest->product_id),
                quantity:    $transferRequest->quantity,
                requester:   $request->user(),
                notes:       "Approved from request #{$transferRequest->id}: {$transferRequest->notes}",
            );

            $transferRequest->update([
                'from_warehouse_id' => $source->id,
                'status'            => 'approved',
                'reviewed_by'       => $request->user()->id,
                'reviewed_at'       => now(),
            ]);

            \App\Events\SystemNotification::dispatch(
                'warehouse.' . $destination->id,
                "Request stok Anda telah Disetujui (Sedang Dikirim)!",
                'success'
            );

            return redirect()->back()->with('success', 'Request approved. Transfer has been initiated.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Super Admin rejects the request.
     */
    public function reject(Request $request, TransferRequest $transferRequest): RedirectResponse
    {
        if ($request->user()->role->value !== 'super_admin') {
            abort(403, 'Only Super Admin can reject transfer requests.');
        }

        if (! $transferRequest->isPending()) {
            return redirect()->back()->with('error', 'This request has already been reviewed.');
        }

        $transferRequest->update([
            'status'      => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        \App\Events\SystemNotification::dispatch(
            'warehouse.' . $transferRequest->to_warehouse_id,
            "Mohon maaf, Request stok Anda Ditolak oleh Pusat.",
            'error'
        );

        return redirect()->back()->with('success', 'Transfer request rejected.');
    }
}
