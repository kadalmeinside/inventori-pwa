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

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/', DashboardController::class)->name('dashboard');

    // Stock Out — Instant Deduction (no approval workflow)
    Route::prefix('stock-outs')->name('stock-outs.')->group(function () {
        Route::get('/',    [StockOutController::class, 'index'])->name('index');
        Route::post('/',   [StockOutController::class, 'store'])->name('store');
    });

    // Stock Transfers — Transit Flow (Initiate + Receive)
    Route::prefix('transfers')->name('transfers.')->group(function () {
        Route::get('/',    [StockTransferController::class, 'index'])->name('index');
        Route::post('/',   [StockTransferController::class, 'store'])->name('store');
        Route::patch('{stockTransfer}/receive', [StockTransferController::class, 'receive'])->name('receive');
    });

    // Transfer Requests — Branch Admin requests stock from Pusat/other branch
    Route::prefix('transfer-requests')->name('transfer-requests.')->group(function () {
        Route::get('/',    [App\Http\Controllers\TransferRequestController::class, 'index'])->name('index');
        Route::post('/',   [App\Http\Controllers\TransferRequestController::class, 'store'])->name('store');
        Route::patch('{transferRequest}/approve', [App\Http\Controllers\TransferRequestController::class, 'approve'])->name('approve');
        Route::patch('{transferRequest}/reject',  [App\Http\Controllers\TransferRequestController::class, 'reject'])->name('reject');
    });

    // Stocks listing & Stock In
    Route::get('/stocks',    [App\Http\Controllers\StockController::class, 'index'])->name('stocks.index');
    Route::post('/stocks/in',[App\Http\Controllers\StockController::class, 'store'])->name('stocks.in');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Master Data (Super Admin only in controllers)
    Route::resource('warehouses', App\Http\Controllers\WarehouseController::class)->except(['create', 'show', 'edit']);
    Route::resource('products',   App\Http\Controllers\ProductController::class)->except(['create', 'show', 'edit']);
    Route::resource('categories', App\Http\Controllers\CategoryController::class)->except(['create', 'show', 'edit']);

    // User Management (Super Admin only)
    Route::resource('users', App\Http\Controllers\UserController::class)->except(['create', 'show', 'edit']);

    // Profile page
    Route::get('/profile',   [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
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
