<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Soft Deletes
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('warehouses', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
        });

        // 2. Indexes for Optimization
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->index(['warehouse_id', 'created_at']);
        });
        Schema::table('stock_entries', function (Blueprint $table) {
            $table->index(['warehouse_id', 'product_id']);
        });
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->index(['destination_warehouse_id', 'status']);
            $table->index(['source_warehouse_id', 'status']);
        });
        Schema::table('transfer_requests', function (Blueprint $table) {
            $table->index(['to_warehouse_id', 'status']);
            $table->index(['from_warehouse_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfer_requests', function (Blueprint $table) {
            $table->dropIndex(['to_warehouse_id', 'status']);
            $table->dropIndex(['from_warehouse_id', 'status']);
        });
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropIndex(['destination_warehouse_id', 'status']);
            $table->dropIndex(['source_warehouse_id', 'status']);
        });
        Schema::table('stock_entries', function (Blueprint $table) {
            $table->dropIndex(['warehouse_id', 'product_id']);
        });
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->dropIndex(['warehouse_id', 'created_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
