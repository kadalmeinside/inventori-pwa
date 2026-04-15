<?php

namespace App\Models;

use App\Enums\TransferStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_warehouse_id',
        'destination_warehouse_id',
        'product_id',
        'quantity',
        'status',
        'notes',
        'requested_by',
        'approved_by',
        'shipped_at',
        'received_at',
    ];

    protected $casts = [
        'status'      => TransferStatus::class,
        'quantity'    => 'integer',
        'shipped_at'  => 'datetime',
        'received_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function inventoryLogs(): MorphMany
    {
        return $this->morphMany(InventoryLog::class, 'reference');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isInTransit(): bool
    {
        return $this->status === TransferStatus::InTransit;
    }

    public function isReceived(): bool
    {
        return $this->status === TransferStatus::Received;
    }
}
