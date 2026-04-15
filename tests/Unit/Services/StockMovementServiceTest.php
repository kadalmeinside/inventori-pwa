<?php

use App\Models\Product;
use App\Models\StockEntry;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helpers ──────────────────────────────────────────────────────────────────

function makeWarehouse(array $attrs = []): Warehouse
{
    return Warehouse::factory()->create($attrs);
}

function makeProduct(array $attrs = []): Product
{
    return Product::factory()->create($attrs);
}

function makeSuperAdmin(): User
{
    return User::factory()->create(['role' => 'super_admin', 'warehouse_id' => null]);
}

function makeBranchAdmin(Warehouse $warehouse): User
{
    return User::factory()->create(['role' => 'branch_admin', 'warehouse_id' => $warehouse->id]);
}

function service(): StockMovementService
{
    return app(StockMovementService::class);
}

// ─── STOCK IN ─────────────────────────────────────────────────────────────────

describe('stockIn()', function () {

    it('creates a stock entry if none exists', function () {
        $warehouse = makeWarehouse();
        $product   = makeProduct();
        $user      = makeSuperAdmin();

        service()->stockIn($warehouse, $product, 50, $user);

        expect(StockEntry::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity')
        )->toBe(50);
    });

    it('increments an existing stock entry correctly', function () {
        $warehouse = makeWarehouse();
        $product   = makeProduct();
        $user      = makeSuperAdmin();

        // Pre-seed balance
        StockEntry::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 100,
        ]);

        service()->stockIn($warehouse, $product, 30, $user);

        expect(StockEntry::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity')
        )->toBe(130);
    });

    it('records correct balance_before and balance_after in ledger', function () {
        $warehouse = makeWarehouse();
        $product   = makeProduct();
        $user      = makeSuperAdmin();

        StockEntry::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 40,
        ]);

        $log = service()->stockIn($warehouse, $product, 10, $user, 'Initial receipt');

        expect($log->balance_before)->toBe(40)
            ->and($log->balance_after)->toBe(50)
            ->and($log->movement_type->value)->toBe('stock_in')
            ->and($log->notes)->toBe('Initial receipt');
    });

    it('throws when quantity is zero or negative', function () {
        $warehouse = makeWarehouse();
        $product   = makeProduct();
        $user      = makeSuperAdmin();

        expect(fn () => service()->stockIn($warehouse, $product, 0, $user))
            ->toThrow(RuntimeException::class);

        expect(fn () => service()->stockIn($warehouse, $product, -5, $user))
            ->toThrow(RuntimeException::class);
    });

});

// ─── STOCK OUT — APPROVAL FLOW ────────────────────────────────────────────────

describe('requestStockOut() + approveStockOut()', function () {

    it('creates a pending stock out WITHOUT deducting stock', function () {
        $warehouse = makeWarehouse();
        $product   = makeProduct();
        $user      = makeBranchAdmin($warehouse);

        StockEntry::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 100,
        ]);

        service()->requestStockOut($warehouse, $product, 20, $user, \App\Enums\StockOutCategory::InternalUse, 'Used in production');

        // Balance MUST NOT change
        expect(StockEntry::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity')
        )->toBe(100);
    });

    it('deducts stock and writes ledger only after approval', function () {
        $warehouse = makeWarehouse();
        $product   = makeProduct();
        $requester = makeBranchAdmin($warehouse);
        $approver  = makeSuperAdmin();

        StockEntry::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 100,
        ]);

        $stockOut = service()->requestStockOut($warehouse, $product, 20, $requester, \App\Enums\StockOutCategory::Sales);
        $log      = service()->approveStockOut($stockOut, $approver);

        expect(StockEntry::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity')
        )->toBe(80);

        expect($log->movement_type->value)->toBe('stock_out')
            ->and($log->balance_before)->toBe(100)
            ->and($log->balance_after)->toBe(80);
    });

    it('throws if approved quantity exceeds available stock (prevents negative balance)', function () {
        $warehouse = makeWarehouse();
        $product   = makeProduct();
        $requester = makeBranchAdmin($warehouse);
        $approver  = makeSuperAdmin();

        StockEntry::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 5,   // Only 5 in stock
        ]);

        $stockOut = service()->requestStockOut($warehouse, $product, 50, $requester, \App\Enums\StockOutCategory::Sales); // Request 50

        expect(fn () => service()->approveStockOut($stockOut, $approver))
            ->toThrow(RuntimeException::class, 'Insufficient stock');

        // Stock must NOT have changed
        expect(StockEntry::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity')
        )->toBe(5);
    });

    it('throws when trying to approve an already approved stock out', function () {
        $warehouse = makeWarehouse();
        $product   = makeProduct();
        $requester = makeBranchAdmin($warehouse);
        $approver  = makeSuperAdmin();

        StockEntry::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 100,
        ]);

        $stockOut = service()->requestStockOut($warehouse, $product, 10, $requester, \App\Enums\StockOutCategory::Sales);
        service()->approveStockOut($stockOut, $approver);
        $stockOut->refresh();

        // Trying to approve again should throw
        expect(fn () => service()->approveStockOut($stockOut, $approver))
            ->toThrow(RuntimeException::class);
    });

});

// ─── STOCK TRANSFER — TRANSIT LOGIC ───────────────────────────────────────────

describe('initiateTransfer() + receiveTransfer()', function () {

    it('decrements source immediately on transfer initiation', function () {
        $sourceWarehouse = makeWarehouse();
        $destWarehouse   = makeWarehouse();
        $product         = makeProduct();
        $requester       = makeBranchAdmin($sourceWarehouse);

        StockEntry::factory()->create([
            'warehouse_id' => $sourceWarehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 100,
        ]);

        service()->initiateTransfer($sourceWarehouse, $destWarehouse, $product, 30, $requester);

        expect(StockEntry::where('warehouse_id', $sourceWarehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity')
        )->toBe(70); // 100 - 30
    });

    it('does NOT credit destination until receiveTransfer() is called', function () {
        $sourceWarehouse = makeWarehouse();
        $destWarehouse   = makeWarehouse();
        $product         = makeProduct();
        $requester       = makeBranchAdmin($sourceWarehouse);

        StockEntry::factory()->create([
            'warehouse_id' => $sourceWarehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 100,
        ]);

        // Destination has NO stock entry yet
        service()->initiateTransfer($sourceWarehouse, $destWarehouse, $product, 30, $requester);

        // Destination balance should still be 0 / not exist
        $destEntry = StockEntry::where('warehouse_id', $destWarehouse->id)
            ->where('product_id', $product->id)
            ->first();

        expect($destEntry)->toBeNull();
    });

    it('credits destination after receiveTransfer() and sets status to received', function () {
        $sourceWarehouse = makeWarehouse();
        $destWarehouse   = makeWarehouse();
        $product         = makeProduct();
        $requester       = makeBranchAdmin($sourceWarehouse);
        $receiver        = makeBranchAdmin($destWarehouse);

        StockEntry::factory()->create([
            'warehouse_id' => $sourceWarehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 100,
        ]);

        $transfer = service()->initiateTransfer($sourceWarehouse, $destWarehouse, $product, 30, $requester);
        $log      = service()->receiveTransfer($transfer, $receiver);

        expect(StockEntry::where('warehouse_id', $destWarehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity')
        )->toBe(30);

        expect($transfer->fresh()->status->value)->toBe('received');
        expect($log->movement_type->value)->toBe('transfer_in')
            ->and($log->balance_before)->toBe(0)
            ->and($log->balance_after)->toBe(30);
    });

    it('prevents negative stock at the source during transfer', function () {
        $sourceWarehouse = makeWarehouse();
        $destWarehouse   = makeWarehouse();
        $product         = makeProduct();
        $requester       = makeBranchAdmin($sourceWarehouse);

        StockEntry::factory()->create([
            'warehouse_id' => $sourceWarehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 10, // Only 10 available
        ]);

        expect(fn () => service()->initiateTransfer($sourceWarehouse, $destWarehouse, $product, 50, $requester))
            ->toThrow(RuntimeException::class, 'Insufficient stock');

        // Source balance must be unchanged
        expect(StockEntry::where('warehouse_id', $sourceWarehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity')
        )->toBe(10);
    });

    it('throws when trying to receive a transfer that is not in transit', function () {
        $sourceWarehouse = makeWarehouse();
        $destWarehouse   = makeWarehouse();
        $product         = makeProduct();
        $requester       = makeBranchAdmin($sourceWarehouse);
        $receiver        = makeBranchAdmin($destWarehouse);

        StockEntry::factory()->create([
            'warehouse_id' => $sourceWarehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 100,
        ]);

        $transfer = service()->initiateTransfer($sourceWarehouse, $destWarehouse, $product, 20, $requester);
        service()->receiveTransfer($transfer, $receiver);
        $transfer->refresh();

        // Receiving again should fail
        expect(fn () => service()->receiveTransfer($transfer, $receiver))
            ->toThrow(RuntimeException::class);
    });

    it('throws when source and destination are the same warehouse', function () {
        $warehouse = makeWarehouse();
        $product   = makeProduct();
        $requester = makeBranchAdmin($warehouse);

        StockEntry::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 100,
        ]);

        expect(fn () => service()->initiateTransfer($warehouse, $warehouse, $product, 10, $requester))
            ->toThrow(RuntimeException::class, 'same');
    });

    it('correctly reports in-transit quantities for a destination', function () {
        $sourceWarehouse = makeWarehouse();
        $destWarehouse   = makeWarehouse();
        $product         = makeProduct();
        $requester       = makeBranchAdmin($sourceWarehouse);

        StockEntry::factory()->create([
            'warehouse_id' => $sourceWarehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 200,
        ]);

        service()->initiateTransfer($sourceWarehouse, $destWarehouse, $product, 40, $requester);
        service()->initiateTransfer($sourceWarehouse, $destWarehouse, $product, 60, $requester);

        $inTransit = service()->getInTransitQuantity($destWarehouse, $product);

        expect($inTransit)->toBe(100); // 40 + 60
    });

});
