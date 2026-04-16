<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\StockTransferController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

// ─── Read-only routes (60 req/menit) ────────────────────────────────────────
Route::middleware(['auth', 'verified', 'throttle:60,1'])->group(function () {

    // Dashboard
    Route::get('/', DashboardController::class)->name('dashboard');

    // Stocks listing
    Route::get('/stocks', [App\Http\Controllers\StockController::class, 'index'])->name('stocks.index');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Master Data (Super Admin only in controllers)
    Route::get('warehouses', [App\Http\Controllers\WarehouseController::class, 'index'])->name('warehouses.index');
    Route::get('products',   [App\Http\Controllers\ProductController::class,   'index'])->name('products.index');
    Route::get('categories', [App\Http\Controllers\CategoryController::class,  'index'])->name('categories.index');

    // User Management (Super Admin only)
    Route::get('users', [App\Http\Controllers\UserController::class, 'index'])->name('users.index');

    // Stock Outs listing
    Route::get('/stock-outs', [App\Http\Controllers\StockOutController::class, 'index'])->name('stock-outs.index');

    // Transfers listing
    Route::get('/transfers', [StockTransferController::class, 'index'])->name('transfers.index');

    // Transfer Requests listing
    Route::get('/transfer-requests', [App\Http\Controllers\TransferRequestController::class, 'index'])->name('transfer-requests.index');

    // Profile page
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
});

// ─── Write routes (20 req/menit) ─────────────────────────────────────────────
Route::middleware(['auth', 'verified', 'throttle:20,1'])->group(function () {

    // Stock In
    Route::post('/stocks/in', [App\Http\Controllers\StockController::class, 'store'])->name('stocks.in');

    // Stock Out — Pemotongan langsung
    Route::post('/stock-outs', [App\Http\Controllers\StockOutController::class, 'store'])->name('stock-outs.store');

    // Transfers — Kirim & Terima
    Route::post('/transfers', [StockTransferController::class, 'store'])->name('transfers.store');
    Route::patch('/transfers/{stockTransfer}/receive', [StockTransferController::class, 'receive'])->name('transfers.receive');

    // Transfer Requests — Ajukan, Setujui, Tolak
    Route::post('/transfer-requests', [App\Http\Controllers\TransferRequestController::class, 'store'])->name('transfer-requests.store');
    Route::patch('/transfer-requests/{transferRequest}/approve', [App\Http\Controllers\TransferRequestController::class, 'approve'])->name('transfer-requests.approve');
    Route::patch('/transfer-requests/{transferRequest}/reject',  [App\Http\Controllers\TransferRequestController::class, 'reject'])->name('transfer-requests.reject');

    // Master Data CRUD (Super Admin only)
    Route::post('warehouses',                  [App\Http\Controllers\WarehouseController::class, 'store'])->name('warehouses.store');
    Route::put('warehouses/{warehouse}',       [App\Http\Controllers\WarehouseController::class, 'update'])->name('warehouses.update');
    Route::delete('warehouses/{warehouse}',    [App\Http\Controllers\WarehouseController::class, 'destroy'])->name('warehouses.destroy');

    Route::post('products',                    [App\Http\Controllers\ProductController::class, 'store'])->name('products.store');
    Route::put('products/{product}',           [App\Http\Controllers\ProductController::class, 'update'])->name('products.update');
    Route::delete('products/{product}',        [App\Http\Controllers\ProductController::class, 'destroy'])->name('products.destroy');

    Route::post('categories',                  [App\Http\Controllers\CategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}',        [App\Http\Controllers\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}',     [App\Http\Controllers\CategoryController::class, 'destroy'])->name('categories.destroy');

    // User Management CRUD (Super Admin only)
    Route::post('users',               [App\Http\Controllers\UserController::class, 'store'])->name('users.store');
    Route::put('users/{user}',         [App\Http\Controllers\UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}',      [App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy');

    // Profile update
    Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

});

// Offline fallback page
Route::get('/offline', fn () => Inertia::render('Offline'))->name('offline');

// ─── PWA Service Worker Virtual Route ────────────────────────────────────
Route::get('/{file}', function ($file) {
    if (in_array($file, ['sw.js', 'registerSW.js', 'manifest.webmanifest']) || str_starts_with($file, 'workbox-')) {
        return response()->file(public_path('build/' . $file), [
            'Content-Type' => str_ends_with($file, '.webmanifest') ? 'application/manifest+json' : 'application/javascript',
            'Service-Worker-Allowed' => '/'
        ]);
    }
    abort(404);
})->where('file', '^(sw\.js|registerSW\.js|manifest\.webmanifest|workbox-.*\.js)$');

// Auth routes (provided by Laravel Breeze)
require __DIR__ . '/auth.php';
