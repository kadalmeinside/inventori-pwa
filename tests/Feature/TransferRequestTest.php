<?php

/**
 * Feature: Transfer Request Lifecycle
 *
 * Menguji alur lengkap Transfer Request:
 * 1. Branch Admin mengajukan permintaan stok
 * 2. Super Admin menyetujui dan transfer langsung dijalankan
 * 3. Super Admin menolak permintaan
 * 4. Proteksi: Branch Admin tidak bisa menyetujui/menolak
 * 5. Request yang sudah direview tidak bisa diubah lagi
 */

use App\Enums\TransferStatus;
use App\Models\Product;
use App\Models\StockEntry;
use App\Models\TransferRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helper factories ─────────────────────────────────────────────────────────

function tr_setup(): array
{
    $pusat  = Warehouse::factory()->create(['name' => 'Pusat',   'code' => 'PST']);
    $cabang = Warehouse::factory()->create(['name' => 'Cabang A', 'code' => 'CBA']);

    $superAdmin  = User::factory()->create(['role' => 'super_admin',  'warehouse_id' => null]);
    $branchAdmin = User::factory()->create(['role' => 'branch_admin', 'warehouse_id' => $cabang->id]);

    $product = Product::factory()->create(['min_stock' => 10]);

    // Pusat punya stok 100
    StockEntry::factory()->create([
        'warehouse_id' => $pusat->id,
        'product_id'   => $product->id,
        'quantity'     => 100,
    ]);

    return compact('pusat', 'cabang', 'superAdmin', 'branchAdmin', 'product');
}

// ─── Tests ────────────────────────────────────────────────────────────────────

describe('Transfer Request — Branch Admin mengajukan', function () {

    it('branch admin dapat mengajukan permintaan stok', function () {
        ['cabang' => $cabang, 'branchAdmin' => $branchAdmin, 'product' => $product] = tr_setup();

        $response = $this->actingAs($branchAdmin)->post(route('transfer-requests.store'), [
            'product_id' => $product->id,
            'quantity'   => 20,
            'notes'      => 'Stok habis, butuh segera',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transfer_requests', [
            'requester_id'     => $branchAdmin->id,
            'to_warehouse_id'  => $cabang->id,
            'product_id'       => $product->id,
            'quantity'         => 20,
            'status'           => 'pending',
            'from_warehouse_id' => null, // Super Admin belum memilih sumber
        ]);
    });

    it('super admin tidak bisa mengajukan permintaan', function () {
        ['superAdmin' => $superAdmin, 'product' => $product] = tr_setup();

        $response = $this->actingAs($superAdmin)->post(route('transfer-requests.store'), [
            'product_id' => $product->id,
            'quantity'   => 10,
        ]);

        $response->assertForbidden();
    });

    it('validasi: quantity harus minimal 1', function () {
        ['branchAdmin' => $branchAdmin, 'product' => $product] = tr_setup();

        $response = $this->actingAs($branchAdmin)->post(route('transfer-requests.store'), [
            'product_id' => $product->id,
            'quantity'   => 0,
        ]);

        $response->assertSessionHasErrors('quantity');
    });

    it('validasi: product_id harus ada di database', function () {
        ['branchAdmin' => $branchAdmin] = tr_setup();

        $response = $this->actingAs($branchAdmin)->post(route('transfer-requests.store'), [
            'product_id' => 99999,
            'quantity'   => 5,
        ]);

        $response->assertSessionHasErrors('product_id');
    });

});

describe('Transfer Request — Super Admin menyetujui', function () {

    it('super admin dapat menyetujui dan stok langsung bergerak', function () {
        ['pusat' => $pusat, 'cabang' => $cabang, 'superAdmin' => $superAdmin, 'branchAdmin' => $branchAdmin, 'product' => $product] = tr_setup();

        $request = TransferRequest::factory()->create([
            'requester_id'     => $branchAdmin->id,
            'to_warehouse_id'  => $cabang->id,
            'product_id'       => $product->id,
            'quantity'         => 30,
            'status'           => 'pending',
        ]);

        $response = $this->actingAs($superAdmin)->patch(route('transfer-requests.approve', $request), [
            'from_warehouse_id' => $pusat->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Status request harus berubah ke approved
        expect($request->fresh()->status)->toBe('approved')
            ->and($request->fresh()->reviewed_by)->toBe($superAdmin->id)
            ->and($request->fresh()->reviewed_at)->not->toBeNull();

        // Stok di Pusat harus berkurang
        expect(StockEntry::where('warehouse_id', $pusat->id)
            ->where('product_id', $product->id)
            ->value('quantity')
        )->toBe(70); // 100 - 30 = 70

        // Transfer harus terbuat dengan status in_transit
        $this->assertDatabaseHas('stock_transfers', [
            'source_warehouse_id'      => $pusat->id,
            'destination_warehouse_id' => $cabang->id,
            'product_id'               => $product->id,
            'quantity'                 => 30,
            'status'                   => TransferStatus::InTransit->value,
        ]);
    });

    it('tidak bisa disetujui jika sumber dan tujuan sama', function () {
        ['cabang' => $cabang, 'superAdmin' => $superAdmin, 'branchAdmin' => $branchAdmin, 'product' => $product] = tr_setup();

        $request = TransferRequest::factory()->create([
            'requester_id'    => $branchAdmin->id,
            'to_warehouse_id' => $cabang->id,
            'product_id'      => $product->id,
            'quantity'        => 10,
            'status'          => 'pending',
        ]);

        $response = $this->actingAs($superAdmin)->patch(route('transfer-requests.approve', $request), [
            'from_warehouse_id' => $cabang->id, // sama dengan tujuan!
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Status tetap pending
        expect($request->fresh()->status)->toBe('pending');
    });

    it('request yang sudah disetujui tidak bisa disetujui lagi', function () {
        ['pusat' => $pusat, 'superAdmin' => $superAdmin, 'branchAdmin' => $branchAdmin, 'product' => $product] = tr_setup();

        $request = TransferRequest::factory()->create([
            'requester_id'    => $branchAdmin->id,
            'to_warehouse_id' => Warehouse::factory()->create()->id,
            'product_id'      => $product->id,
            'quantity'        => 5,
            'status'          => 'approved', // sudah diapprove
            'reviewed_by'     => $superAdmin->id,
        ]);

        $response = $this->actingAs($superAdmin)->patch(route('transfer-requests.approve', $request), [
            'from_warehouse_id' => $pusat->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });

    it('branch admin tidak bisa menyetujui request', function () {
        ['pusat' => $pusat, 'branchAdmin' => $branchAdmin, 'product' => $product] = tr_setup();

        $request = TransferRequest::factory()->create([
            'requester_id'    => $branchAdmin->id,
            'to_warehouse_id' => $branchAdmin->warehouse_id,
            'product_id'      => $product->id,
            'quantity'        => 5,
            'status'          => 'pending',
        ]);

        $response = $this->actingAs($branchAdmin)->patch(route('transfer-requests.approve', $request), [
            'from_warehouse_id' => $pusat->id,
        ]);

        $response->assertForbidden();
    });

});

describe('Transfer Request — Super Admin menolak', function () {

    it('super admin dapat menolak permintaan', function () {
        ['superAdmin' => $superAdmin, 'branchAdmin' => $branchAdmin, 'product' => $product] = tr_setup();

        $request = TransferRequest::factory()->create([
            'requester_id'    => $branchAdmin->id,
            'to_warehouse_id' => $branchAdmin->warehouse_id,
            'product_id'      => $product->id,
            'quantity'        => 50,
            'status'          => 'pending',
        ]);

        $response = $this->actingAs($superAdmin)->patch(route('transfer-requests.reject', $request));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        expect($request->fresh()->status)->toBe('rejected')
            ->and($request->fresh()->reviewed_by)->toBe($superAdmin->id);
    });

    it('request yang sudah ditolak tidak bisa ditolak lagi', function () {
        ['superAdmin' => $superAdmin, 'branchAdmin' => $branchAdmin, 'product' => $product] = tr_setup();

        $request = TransferRequest::factory()->create([
            'requester_id'    => $branchAdmin->id,
            'to_warehouse_id' => $branchAdmin->warehouse_id,
            'product_id'      => $product->id,
            'quantity'        => 5,
            'status'          => 'rejected',
            'reviewed_by'     => $superAdmin->id,
        ]);

        $response = $this->actingAs($superAdmin)->patch(route('transfer-requests.reject', $request));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });

    it('branch admin tidak bisa menolak request', function () {
        ['branchAdmin' => $branchAdmin, 'product' => $product] = tr_setup();

        $request = TransferRequest::factory()->create([
            'requester_id'    => $branchAdmin->id,
            'to_warehouse_id' => $branchAdmin->warehouse_id,
            'product_id'      => $product->id,
            'quantity'        => 5,
            'status'          => 'pending',
        ]);

        $response = $this->actingAs($branchAdmin)->patch(route('transfer-requests.reject', $request));

        $response->assertForbidden();
    });

});

describe('Transfer Request — Halaman Index', function () {

    it('branch admin hanya melihat request gudangnya sendiri', function () {
        ['cabang' => $cabang, 'branchAdmin' => $branchAdmin, 'product' => $product] = tr_setup();

        $otherWarehouse = Warehouse::factory()->create();
        $otherAdmin     = User::factory()->create(['role' => 'branch_admin', 'warehouse_id' => $otherWarehouse->id]);

        // Request milik branchAdmin
        TransferRequest::factory()->create([
            'requester_id'    => $branchAdmin->id,
            'to_warehouse_id' => $cabang->id,
            'product_id'      => $product->id,
            'quantity'        => 10,
            'status'          => 'pending',
        ]);

        // Request milik cabang lain
        TransferRequest::factory()->create([
            'requester_id'    => $otherAdmin->id,
            'to_warehouse_id' => $otherWarehouse->id,
            'product_id'      => $product->id,
            'quantity'        => 5,
            'status'          => 'pending',
        ]);

        $response = $this->actingAs($branchAdmin)->get(route('transfer-requests.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->has('requests.data', 1) // hanya 1 yang terlihat
        );
    });

    it('super admin melihat semua request dari semua cabang', function () {
        ['pusat' => $pusat, 'cabang' => $cabang, 'superAdmin' => $superAdmin, 'branchAdmin' => $branchAdmin, 'product' => $product] = tr_setup();

        TransferRequest::factory()->count(3)->create([
            'requester_id'    => $branchAdmin->id,
            'to_warehouse_id' => $cabang->id,
            'product_id'      => $product->id,
            'quantity'        => 5,
            'status'          => 'pending',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('transfer-requests.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->has('requests.data', 3) // semua terlihat
        );
    });

    it('guest tidak bisa mengakses halaman transfer requests', function () {
        $response = $this->get(route('transfer-requests.index'));
        $response->assertRedirect(route('login'));
    });

});
