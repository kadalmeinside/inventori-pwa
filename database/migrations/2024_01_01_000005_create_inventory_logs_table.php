<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('stock_entry_id')->constrained('stock_entries');
            $table->string('movement_type'); // stock_in | stock_out | transfer_out | transfer_in
            $table->bigInteger('quantity');  // positive or negative
            $table->unsignedBigInteger('balance_before');
            $table->unsignedBigInteger('balance_after');
            // Polymorphic reference: links to StockOut or StockTransfer
            $table->nullableMorphs('reference');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['warehouse_id', 'product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};
