<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Enums\StockOutStatus;
use App\Enums\TransferStatus;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\StockEntry;
use App\Models\StockOut;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockMovementService
{
    // ──────────────────────────────────────────────────────────────────────────
    // STOCK IN
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Add stock to a warehouse for the given product.
     * Creates a stock_entry if one does not exist, then writes the ledger.
     *
     * @throws RuntimeException
     */
    public function stockIn(
        Warehouse $warehouse,
        Product   $product,
        int       $quantity,
        User      $performedBy,
        ?string   $notes = null,
    ): InventoryLog {
        if ($quantity <= 0) {
            throw new RuntimeException('Stock In quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($warehouse, $product, $quantity, $performedBy, $notes) {
            // Lock the row to prevent race conditions
            $entry = StockEntry::lockForUpdate()->firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'product_id' => $product->id],
                ['quantity'     => 0],
            );

            $balanceBefore = $entry->quantity;
            $entry->increment('quantity', $quantity);
            $entry->refresh();

            return $this->writeLog(
                warehouse:     $warehouse,
                product:       $product,
                entry:         $entry,
                type:          StockMovementType::StockIn,
                quantity:      $quantity,
                balanceBefore: $balanceBefore,
                balanceAfter:  $entry->quantity,
                reference:     null,
                notes:         $notes,
                performedBy:   $performedBy,
            );
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // STOCK OUT — instant deduction (no approval workflow)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Instantly deduct stock from a warehouse.
     * Branch Admins can only deduct from their own warehouse.
     * All actions are permanently recorded in the InventoryLog audit trail.
     *
     * @throws RuntimeException if stock is insufficient.
     */
    public function instantStockOut(
        Warehouse $warehouse,
        Product   $product,
        int       $quantity,
        User      $performedBy,
        \App\Enums\StockOutCategory $category,
        ?string   $reason = null,
    ): InventoryLog {
        if ($quantity <= 0) {
            throw new RuntimeException('Stock Out quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($warehouse, $product, $quantity, $performedBy, $category, $reason) {
            $entry = StockEntry::lockForUpdate()
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $product->id)
                ->firstOrFail();

            if (! $entry->hasSufficientStock($quantity)) {
                throw new RuntimeException(
                    "Insufficient stock. Available: {$entry->quantity}, Requested: {$quantity}."
                );
            }

            $balanceBefore = $entry->quantity;
            $entry->decrement('quantity', $quantity);
            $entry->refresh();

            // Create standard stock out record for audit trail history
            $stockOut = \App\Models\StockOut::create([
                'warehouse_id' => $warehouse->id,
                'product_id'   => $product->id,
                'quantity'     => $quantity,
                'status'       => \App\Enums\StockOutStatus::Approved,
                'category'     => $category,
                'reason'       => $reason,
                'requested_by' => $performedBy->id,
                'approved_by'  => $performedBy->id,
                'approved_at'  => now(),
            ]);

            return $this->writeLog(
                warehouse:     $warehouse,
                product:       $product,
                entry:         $entry,
                type:          StockMovementType::StockOut,
                quantity:      -$quantity,
                balanceBefore: $balanceBefore,
                balanceAfter:  $entry->quantity,
                reference:     $stockOut,
                notes:         "[{$category->value}] {$reason}",
                performedBy:   $performedBy,
            );
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // STOCK TRANSFER — transit logic
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Initiate a transfer from source to destination.
     *
     * - Source stock is IMMEDIATELY debited.
     * - Destination stock is NOT yet credited (stock is "in transit").
     * - Transfer status becomes `in_transit`.
     *
     * @throws RuntimeException for same-warehouse transfer, insufficient stock, etc.
     */
    public function initiateTransfer(
        Warehouse $source,
        Warehouse $destination,
        Product   $product,
        int       $quantity,
        User      $requester,
        ?string   $notes = null,
    ): StockTransfer {
        if ($quantity <= 0) {
            throw new RuntimeException('Transfer quantity must be greater than zero.');
        }

        if ($source->id === $destination->id) {
            throw new RuntimeException('Source and destination warehouse cannot be the same.');
        }

        return DB::transaction(function () use ($source, $destination, $product, $quantity, $requester, $notes) {
            $sourceEntry = StockEntry::lockForUpdate()
                ->where('warehouse_id', $source->id)
                ->where('product_id', $product->id)
                ->firstOrFail();

            if (! $sourceEntry->hasSufficientStock($quantity)) {
                throw new RuntimeException(
                    "Insufficient stock in source warehouse [{$source->name}]. "
                    . "Available: {$sourceEntry->quantity}, Requested: {$quantity}."
                );
            }

            // Create transfer record first
            $transfer = StockTransfer::create([
                'source_warehouse_id'      => $source->id,
                'destination_warehouse_id' => $destination->id,
                'product_id'               => $product->id,
                'quantity'                 => $quantity,
                'status'                   => TransferStatus::InTransit,
                'notes'                    => $notes,
                'requested_by'             => $requester->id,
                'shipped_at'               => now(),
            ]);

            // Debit source
            $balanceBefore = $sourceEntry->quantity;
            $sourceEntry->decrement('quantity', $quantity);
            $sourceEntry->refresh();

            $this->writeLog(
                warehouse:     $source,
                product:       $product,
                entry:         $sourceEntry,
                type:          StockMovementType::TransferOut,
                quantity:      -$quantity,
                balanceBefore: $balanceBefore,
                balanceAfter:  $sourceEntry->quantity,
                reference:     $transfer,
                notes:         "Transfer out to [{$destination->name}]. In transit.",
                performedBy:   $requester,
            );

            return $transfer;
        });
    }

    /**
     * Receive a transfer at the destination warehouse.
     *
     * - Destination stock is credited.
     * - Transfer status becomes `received`.
     * - The entire operation is atomic: if it fails, the destination does NOT get credited.
     *
     * @throws RuntimeException if transfer is not in `in_transit` status.
     */
    public function receiveTransfer(StockTransfer $transfer, User $receiver): InventoryLog
    {
        if (! $transfer->isInTransit()) {
            throw new RuntimeException(
                "Cannot receive a transfer with status [{$transfer->status->value}]. "
                . 'Only in-transit transfers can be received.'
            );
        }

        return DB::transaction(function () use ($transfer, $receiver) {
            $destWarehouse = $transfer->destinationWarehouse;
            $product       = $transfer->product;

            // Upsert destination stock entry (may not exist yet)
            $destEntry = StockEntry::lockForUpdate()->firstOrCreate(
                ['warehouse_id' => $destWarehouse->id, 'product_id' => $product->id],
                ['quantity'     => 0],
            );

            $balanceBefore = $destEntry->quantity;
            $destEntry->increment('quantity', $transfer->quantity);
            $destEntry->refresh();

            $transfer->update([
                'status'      => TransferStatus::Received,
                'approved_by' => $receiver->id,
                'received_at' => now(),
            ]);

            return $this->writeLog(
                warehouse:     $destWarehouse,
                product:       $product,
                entry:         $destEntry,
                type:          StockMovementType::TransferIn,
                quantity:      $transfer->quantity,
                balanceBefore: $balanceBefore,
                balanceAfter:  $destEntry->quantity,
                reference:     $transfer,
                notes:         "Transfer received from [{$transfer->sourceWarehouse->name}].",
                performedBy:   $receiver,
            );
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LEDGER QUERY HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Calculate the total quantity currently "in transit" bound for a warehouse.
     */
    public function getInTransitQuantity(Warehouse $destination, Product $product): int
    {
        return StockTransfer::where('destination_warehouse_id', $destination->id)
            ->where('product_id', $product->id)
            ->where('status', TransferStatus::InTransit->value)
            ->sum('quantity');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private function writeLog(
        Warehouse          $warehouse,
        Product            $product,
        StockEntry         $entry,
        StockMovementType  $type,
        int                $quantity,
        int                $balanceBefore,
        int                $balanceAfter,
        mixed              $reference,
        ?string            $notes,
        User               $performedBy,
    ): InventoryLog {
        $log = InventoryLog::create([
            'warehouse_id'   => $warehouse->id,
            'product_id'     => $product->id,
            'stock_entry_id' => $entry->id,
            'movement_type'  => $type->value,
            'quantity'       => $quantity,
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceAfter,
            'reference_id'   => $reference?->id,
            'reference_type' => $reference ? get_class($reference) : null,
            'notes'          => $notes,
            'created_by'     => $performedBy->id,
        ]);

        \App\Events\StockUpdated::dispatch(
            $warehouse->id,
            $product->id,
            $type->value
        );

        return $log;
    }
}
