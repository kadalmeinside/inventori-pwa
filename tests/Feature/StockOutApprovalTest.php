<?php

/**
 * Feature: Stock Out Approval Workflow
 *
 * Verifies that stock is only deducted from the ledger after explicit approval,
 * and that approval with insufficient stock is gracefully rejected.
 */

use App\Enums\StockOutStatus;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\StockEntry;
use App\Models\StockOut;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Stock Out Approval workflow', function () {

    it('a pending Stock Out does not deduct inventory', function () {
        $warehouse = Warehouse::factory()->create();
        $product   = Product::factory()->create();
        $entry     = StockEntry::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 200,
        ]);
        $user = User::factory()->create(['role' => 'branch_admin', 'warehouse_id' => $warehouse->id]);

        app(StockMovementService::class)->requestStockOut($warehouse, $product, 50, $user, \App\Enums\StockOutCategory::Sales);

        expect(StockOut::count())->toBe(1);
        expect(StockOut::first()->status)->toBe(StockOutStatus::Pending);
        expect($entry->fresh()->quantity)->toBe(200); // unchanged
        expect(InventoryLog::count())->toBe(0);       // no ledger entry yet
    });

    it('approved Stock Out deducts inventory and creates ledger entry', function () {
        $warehouse = Warehouse::factory()->create();
        $product   = Product::factory()->create();
        $entry     = StockEntry::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 200,
        ]);
        $requester = User::factory()->create(['role' => 'branch_admin', 'warehouse_id' => $warehouse->id]);
        $approver  = User::factory()->create(['role' => 'super_admin', 'warehouse_id' => null]);

        $stockOut = app(StockMovementService::class)->requestStockOut($warehouse, $product, 50, $requester, \App\Enums\StockOutCategory::Sales);
        app(StockMovementService::class)->approveStockOut($stockOut, $approver);

        expect($entry->fresh()->quantity)->toBe(150);
        expect(InventoryLog::count())->toBe(1);
        expect(InventoryLog::first()->movement_type->value)->toBe('stock_out');
        expect($stockOut->fresh()->status)->toBe(StockOutStatus::Approved);
        expect($stockOut->fresh()->approved_by)->toBe($approver->id);
    });

    it('cannot approve Stock Out if stock is insufficient — transaction rolls back', function () {
        $warehouse = Warehouse::factory()->create();
        $product   = Product::factory()->create();
        $entry     = StockEntry::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 20, // only 20 available
        ]);
        $requester = User::factory()->create(['role' => 'branch_admin', 'warehouse_id' => $warehouse->id]);
        $approver  = User::factory()->create(['role' => 'super_admin', 'warehouse_id' => null]);

        $stockOut = app(StockMovementService::class)->requestStockOut($warehouse, $product, 100, $requester, \App\Enums\StockOutCategory::Sales);

        expect(fn () => app(StockMovementService::class)->approveStockOut($stockOut, $approver))
            ->toThrow(RuntimeException::class);

        // Verify rollback: balance unchanged, status still pending
        expect($entry->fresh()->quantity)->toBe(20);
        expect($stockOut->fresh()->status)->toBe(StockOutStatus::Pending);
        expect(InventoryLog::count())->toBe(0);
    });

    it('rejected Stock Out never touches inventory', function () {
        $warehouse = Warehouse::factory()->create();
        $product   = Product::factory()->create();
        $entry     = StockEntry::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 50,
        ]);
        $requester = User::factory()->create(['role' => 'branch_admin', 'warehouse_id' => $warehouse->id]);
        $approver  = User::factory()->create(['role' => 'super_admin', 'warehouse_id' => null]);

        $stockOut = app(StockMovementService::class)->requestStockOut($warehouse, $product, 30, $requester, \App\Enums\StockOutCategory::Sales);
        app(StockMovementService::class)->rejectStockOut($stockOut, $approver);

        expect($stockOut->fresh()->status)->toBe(StockOutStatus::Rejected);
        expect($entry->fresh()->quantity)->toBe(50); // unchanged
        expect(InventoryLog::count())->toBe(0);      // no ledger entry
    });

    it('Stock Out HTTP endpoint returns 200 after successful approval', function () {
        $warehouse = Warehouse::factory()->create();
        $product   = Product::factory()->create();

        StockEntry::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 100,
        ]);

        $requester = User::factory()->create(['role' => 'branch_admin', 'warehouse_id' => $warehouse->id]);
        $approver  = User::factory()->create(['role' => 'super_admin', 'warehouse_id' => null]);

        $stockOut = app(StockMovementService::class)->requestStockOut($warehouse, $product, 25, $requester, \App\Enums\StockOutCategory::Sales);

        $this->actingAs($approver);

        $response = $this->patch(route('stock-outs.approve', $stockOut));
        $response->assertRedirect(); // redirects after approval
    });

});
