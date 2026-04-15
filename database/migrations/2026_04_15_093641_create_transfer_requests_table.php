<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_requests', function (Blueprint $table) {
            $table->id();

            // Who is requesting
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();

            // From warehouse (null = Super Admin will pick the source when approving)
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();

            // To warehouse = the requesting branch's own warehouse
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->text('notes')->nullable();

            // Status: pending, approved, rejected
            $table->string('status')->default('pending');

            // Who reviewed it and when
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_requests');
    }
};
