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
        // 1. Soft Deletes (warehouses and products already have it in their migrations)
        if (!Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 2. Indexes for Optimization (Idempotent)
        $tablesAndIndexes = [
            'inventory_logs' => [['warehouse_id', 'created_at']],
            'stock_entries' => [['warehouse_id', 'product_id']],
            'stock_transfers' => [
                ['destination_warehouse_id', 'status'],
                ['source_warehouse_id', 'status']
            ],
            'transfer_requests' => [
                ['to_warehouse_id', 'status'],
                ['from_warehouse_id', 'status']
            ],
        ];

        foreach ($tablesAndIndexes as $tableName => $indexes) {
            foreach ($indexes as $indexColumns) {
                try {
                    Schema::table($tableName, function (Blueprint $table) use ($indexColumns) {
                        $table->index($indexColumns);
                    });
                } catch (\Exception $e) {
                    // Ignore duplicate key errors (1061)
                    if (!str_contains($e->getMessage(), '1061 Duplicate key name')) {
                        throw $e;
                    }
                }
            }
        }
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

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
