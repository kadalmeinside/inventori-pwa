<?php

/**
 * Feature: Authorization & Multi-Warehouse Data Isolation
 *
 * Ensures Branch Admin A cannot see or interact with Branch B's data.
 * Super Admin bypasses all warehouse restrictions.
 */

use App\Models\Product;
use App\Models\StockEntry;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

// ─── Helper factories ─────────────────────────────────────────────────────────

function authWarehouses(): array
{
    return [
        'warehouseA' => Warehouse::factory()->create(['name' => 'Warehouse A', 'code' => 'WH-A']),
        'warehouseB' => Warehouse::factory()->create(['name' => 'Warehouse B', 'code' => 'WH-B']),
    ];
}

// ─── Suite ────────────────────────────────────────────────────────────────────

describe('Branch Admin isolation', function () {

    it('Branch Admin A cannot view Warehouse B via Policy', function () {
        ['warehouseA' => $warehouseA, 'warehouseB' => $warehouseB] = authWarehouses();

        $adminA = User::factory()->create([
            'role'         => 'branch_admin',
            'warehouse_id' => $warehouseA->id,
        ]);

        // Authenticate as Admin A
        $this->actingAs($adminA);

        // Policy check: can Admin A view Warehouse B? → should be false
        expect(Gate::allows('view', $warehouseB))->toBeFalse();
        // Admin A can view their own
        expect(Gate::allows('view', $warehouseA))->toBeTrue();
    });

    it('Branch Admin A cannot create a transfer FROM Warehouse B', function () {
        ['warehouseA' => $warehouseA, 'warehouseB' => $warehouseB] = authWarehouses();
        $product = Product::factory()->create();

        $adminA = User::factory()->create([
            'role'         => 'branch_admin',
            'warehouse_id' => $warehouseA->id,
        ]);

        // Seed stock in Warehouse B
        StockEntry::factory()->create([
            'warehouse_id' => $warehouseB->id,
            'product_id'   => $product->id,
            'quantity'     => 100,
        ]);

        $this->actingAs($adminA);

        // Policy check: create transfer FROM warehouse B → unauthorized
        expect(Gate::allows('create', [StockTransfer::class, $warehouseB]))->toBeFalse();
    });

    it('Branch Admin A cannot receive a transfer destined for Warehouse B', function () {
        ['warehouseA' => $warehouseA, 'warehouseB' => $warehouseB] = authWarehouses();
        $product = Product::factory()->create();

        $adminA = User::factory()->create([
            'role'         => 'branch_admin',
            'warehouse_id' => $warehouseA->id,
        ]);

        StockEntry::factory()->create([
            'warehouse_id' => $warehouseA->id,
            'product_id'   => $product->id,
            'quantity'     => 100,
        ]);

        // Create a transfer going from A → B (Admin B should receive it)
        $adminSuperUser = User::factory()->create(['role' => 'super_admin', 'warehouse_id' => null]);
        $transfer = app(StockMovementService::class)
            ->initiateTransfer($warehouseA, $warehouseB, $product, 20, $adminSuperUser);

        $this->actingAs($adminA);

        // Admin A (belongs to Warehouse A) cannot receive into Warehouse B
        expect(Gate::allows('receive', $transfer))->toBeFalse();
    });

    it('Branch Admin A CANNOT directly request stock out from Warehouse B via HTTP', function () {
        ['warehouseA' => $warehouseA, 'warehouseB' => $warehouseB] = authWarehouses();
        Product::factory()->create();

        $adminA = User::factory()->create([
            'role'         => 'branch_admin',
            'warehouse_id' => $warehouseA->id,
        ]);

        $this->actingAs($adminA);

        // Attempt to call stock-out on warehouse B
        $response = $this->post(route('stock-outs.store'), [
            'warehouse_id' => $warehouseB->id,
            'product_id'   => Product::factory()->create()->id,
            'quantity'     => 10,
            'category'     => \App\Enums\StockOutCategory::Sales->value,
            'reason'       => 'unauthorized attempt',
        ]);

        // Should be forbidden
        $response->assertForbidden();
    });

});

describe('Super Admin full access', function () {

    it('Super Admin can view ANY warehouse', function () {
        ['warehouseA' => $warehouseA, 'warehouseB' => $warehouseB] = authWarehouses();

        $superAdmin = User::factory()->create([
            'role'         => 'super_admin',
            'warehouse_id' => null,
        ]);

        $this->actingAs($superAdmin);

        expect(Gate::allows('view', $warehouseA))->toBeTrue();
        expect(Gate::allows('view', $warehouseB))->toBeTrue();
    });

    it('Super Admin can initiate transfer between any warehouses', function () {
        ['warehouseA' => $warehouseA, 'warehouseB' => $warehouseB] = authWarehouses();

        $superAdmin = User::factory()->create([
            'role'         => 'super_admin',
            'warehouse_id' => null,
        ]);

        $this->actingAs($superAdmin);

        expect(Gate::allows('create', [StockTransfer::class, $warehouseA]))->toBeTrue();
        expect(Gate::allows('create', [StockTransfer::class, $warehouseB]))->toBeTrue();
    });

});
