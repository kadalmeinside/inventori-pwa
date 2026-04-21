<?php

namespace App\Http\Controllers;

use App\Models\StockEntry;
use App\Models\TransferRequest;
use App\Models\Warehouse;
use App\Services\PushNotificationService;
use App\Services\StockMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;

class TransferRequestController extends Controller
{
    public function __construct(
        private readonly StockMovementService    $service,
        private readonly PushNotificationService $push,
    ) {}

    /**
     * List all transfer requests.
     * Branch Admin sees only their own; Super Admin sees all.
     */
    public function index(Request $request): Response
    {
        $user  = $request->user();
        $query = TransferRequest::with(['requester', 'fromWarehouse', 'toWarehouse', 'product', 'reviewer'])
            ->visibleToUser($user);

        $requests = $query->latest()->paginate(15);

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

        return Inertia::render('TransferRequests/Index', [
            'requests'   => $requests,
            'warehouses' => $warehouses,
            'products'   => $products,
            'stocks'     => $stocks,
        ]);
    }

    /**
     * Branch Admin creates a transfer request.
     * They specify destination (auto = their warehouse) and desired product/quantity.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        Gate::authorize('create', TransferRequest::class);

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

        // ─── Push Notification ke Super Admin ────────────────────────────────
        $this->push->sendToSuperAdmins([
            'title' => "📦 Request Transfer Baru",
            'body'  => "Cabang {$user->warehouse->name} mengajukan permintaan transfer stok baru.",
            'url'   => '/transfer-requests',
            'tag'   => 'transfer-request-new',
        ]);

        return redirect()->back()->with('success', 'Transfer request submitted. Awaiting approval.');
    }

    /**
     * Super Admin approves the request and initiates the actual transfer.
     */
    public function approve(Request $request, TransferRequest $transferRequest): RedirectResponse
    {
        Gate::authorize('approve', $transferRequest);

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

            // ─── Push Notification ke Branch Admin requester ──────────────────
            $transferRequest->loadMissing(['requester', 'product']);
            $this->push->sendToUser($transferRequest->requester, [
                'title' => "✅ Request Transfer Disetujui",
                'body'  => "Permintaan {$transferRequest->product->name} \u00d7{$transferRequest->quantity} dari {$source->name} sedang dikirim.",
                'url'   => '/transfer-requests',
                'tag'   => "transfer-approved-{$transferRequest->id}",
            ]);

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
        Gate::authorize('reject', $transferRequest);

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

        // ─── Push Notification ke Branch Admin requester ──────────────────────
        $transferRequest->loadMissing(['requester', 'product']);
        $this->push->sendToUser($transferRequest->requester, [
            'title' => "❌ Request Transfer Ditolak",
            'body'  => "Permintaan {$transferRequest->product->name} \u00d7{$transferRequest->quantity} tidak dapat diproses saat ini.",
            'url'   => '/transfer-requests',
            'tag'   => "transfer-rejected-{$transferRequest->id}",
        ]);

        return redirect()->back()->with('success', 'Transfer request rejected.');
    }
}
