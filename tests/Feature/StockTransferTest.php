<?php

/**
 * Feature: Full Stock Transfer Lifecycle
 *
 * Tests the complete transfer flow:
 * 1. Initiate: source decrements, destination unchanged (in transit)
 * 2. Receive:  destination increments, status → received
 * 3. Atomicity: if receive fails mid-way, DB rolls back
 */

use App\Enums\TransferStatus;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\StockEntry;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// Helpers
function transfer_warehouses(): array
{
    return [
        'src'  => Warehouse::factory()->create(['name' => 'Source WH',      'code' => 'SRC']),
        'dest' => Warehouse::factory()->create(['name' => 'Destination WH',  'code' => 'DST']),
    ];
}

describe('Stock Transfer — full lifecycle', function () {

    it('step 1: initiating transfer debits source; destination stays zero', function () {
        ['src' => $src, 'dest' => $dest] = transfer_warehouses();
        $product   = Product::factory()->create();
        $requester = User::factory()->create(['role' => 'super_admin', 'warehouse_id' => null]);

        StockEntry::factory()->create([
            'warehouse_id' => $src->id,
            'product_id'   => $product->id,
            'quantity'     => 150,
        ]);

        $transfer = app(StockMovementService::class)
            ->initiateTransfer($src, $dest, $product, 50, $requester);

        // Source: 150 - 50 = 100
        expect(StockEntry::where('warehouse_id', $src->id)
            ->where('product_id', $product->id)
            ->value('quantity')
        )->toBe(100);

        // Destination: no entry yet (0 in transit, not credited)
        expect(StockEntry::where('warehouse_id', $dest->id)
            ->where('product_id', $product->id)
            ->exists()
        )->toBeFalse();

        expect($transfer->status)->toBe(TransferStatus::InTransit);
        expect($transfer->shipped_at)->not->toBeNull();

        // A transfer_out log must exist for the source
        $log = InventoryLog::where('warehouse_id', $src->id)->first();
        expect($log->movement_type->value)->toBe('transfer_out')
            ->and($log->balance_before)->toBe(150)
            ->and($log->balance_after)->toBe(100);
    });

    it('step 2: receiving transfer credits destination and status becomes received', function () {
        ['src' => $src, 'dest' => $dest] = transfer_warehouses();
        $product   = Product::factory()->create();
        $requester = User::factory()->create(['role' => 'super_admin', 'warehouse_id' => null]);
        $receiver  = User::factory()->create(['role' => 'branch_admin', 'warehouse_id' => $dest->id]);

        StockEntry::factory()->create([
            'warehouse_id' => $src->id,
            'product_id'   => $product->id,
            'quantity'     => 150,
        ]);

        $transfer = app(StockMovementService::class)
            ->initiateTransfer($src, $dest, $product, 50, $requester);

        $log = app(StockMovementService::class)
            ->receiveTransfer($transfer, $receiver);

        // Destination should now have 50
        expect(StockEntry::where('warehouse_id', $dest->id)
            ->where('product_id', $product->id)
            ->value('quantity')
        )->toBe(50);

        expect($transfer->fresh()->status)->toBe(TransferStatus::Received);
        expect($transfer->fresh()->received_at)->not->toBeNull();

        expect($log->movement_type->value)->toBe('transfer_in')
            ->and($log->balance_before)->toBe(0)
            ->and($log->balance_after)->toBe(50)
            ->and($log->warehouse_id)->toBe($dest->id);
    });

    it('step 3: atomic rollback — destination NOT credited if receiveTransfer fails', function () {
        ['src' => $src, 'dest' => $dest] = transfer_warehouses();
        $product   = Product::factory()->create();
        $requester = User::factory()->create(['role' => 'super_admin', 'warehouse_id' => null]);
        $receiver  = User::factory()->create(['role' => 'branch_admin', 'warehouse_id' => $dest->id]);

        StockEntry::factory()->create([
            'warehouse_id' => $src->id,
            'product_id'   => $product->id,
            'quantity'     => 100,
        ]);

        $transfer = app(StockMovementService::class)
            ->initiateTransfer($src, $dest, $product, 40, $requester);

        // Simulate a failure inside the transaction by injecting a DB listener
        // that throws an exception AFTER the destination stock is incremented
        // but BEFORE the transaction is committed.
        DB::listen(function ($query) {
            if (str_contains($query->sql, 'inventory_logs')) {
                throw new RuntimeException('Simulated DB failure on log write.');
            }
        });

        try {
            app(StockMovementService::class)->receiveTransfer($transfer, $receiver);
        } catch (\Throwable) {
            // Expected to fail
        }

        // DB::listen cleanup
        DB::flushQueryLog();

        // Destination balance must NOT have been updated (full rollback)
        $destEntry = StockEntry::where('warehouse_id', $dest->id)
            ->where('product_id', $product->id)
            ->first();

        expect($destEntry)->toBeNull(); // no entry = no credit happened

        // Transfer status must still be 'in_transit' (not received)
        expect($transfer->fresh()->status)->toBe(TransferStatus::InTransit);
    });

    it('transfer complete end-to-end: source -50, destination +50, two ledger entries', function () {
        ['src' => $src, 'dest' => $dest] = transfer_warehouses();
        $product   = Product::factory()->create();
        $requester = User::factory()->create(['role' => 'super_admin', 'warehouse_id' => null]);
        $receiver  = User::factory()->create(['role' => 'branch_admin', 'warehouse_id' => $dest->id]);

        StockEntry::factory()->create([
            'warehouse_id' => $src->id,
            'product_id'   => $product->id,
            'quantity'     => 200,
        ]);

        $transfer = app(StockMovementService::class)->initiateTransfer($src, $dest, $product, 50, $requester);
        app(StockMovementService::class)->receiveTransfer($transfer, $receiver);

        expect(StockEntry::where('warehouse_id', $src->id)->where('product_id', $product->id)->value('quantity'))
            ->toBe(150);

        expect(StockEntry::where('warehouse_id', $dest->id)->where('product_id', $product->id)->value('quantity'))
            ->toBe(50);

        // Should have exactly 2 ledger entries: transfer_out + transfer_in
        expect(InventoryLog::count())->toBe(2);
        expect(InventoryLog::where('movement_type', 'transfer_out')->count())->toBe(1);
        expect(InventoryLog::where('movement_type', 'transfer_in')->count())->toBe(1);
    });

    it('getInTransitQuantity counts only in-transit transfers', function () {
        ['src' => $src, 'dest' => $dest] = transfer_warehouses();
        $product   = Product::factory()->create();
        $requester = User::factory()->create(['role' => 'super_admin', 'warehouse_id' => null]);
        $receiver  = User::factory()->create(['role' => 'branch_admin', 'warehouse_id' => $dest->id]);

        StockEntry::factory()->create([
            'warehouse_id' => $src->id,
            'product_id'   => $product->id,
            'quantity'     => 500,
        ]);

        $svc = app(StockMovementService::class);

        // Initiate 3 transfers: 20 + 30 + 50 = 100 in transit
        $t1 = $svc->initiateTransfer($src, $dest, $product, 20, $requester);
        $t2 = $svc->initiateTransfer($src, $dest, $product, 30, $requester);
        $t3 = $svc->initiateTransfer($src, $dest, $product, 50, $requester);

        // Receive t1 → it's no longer in transit
        $svc->receiveTransfer($t1, $receiver);

        $inTransit = $svc->getInTransitQuantity($dest, $product);

        // Only t2 (30) and t3 (50) are still in-transit
        expect($inTransit)->toBe(80);
    });

});
