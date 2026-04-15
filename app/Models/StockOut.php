<?php

namespace App\Models;

use App\Enums\StockOutStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class StockOut extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'quantity',
        'status',
        'category',
        'reason',
        'requested_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'status'      => StockOutStatus::class,
        'category'    => \App\Enums\StockOutCategory::class,
        'quantity'    => 'integer',
        'approved_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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

    public function isPending(): bool
    {
        return $this->status === StockOutStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === StockOutStatus::Approved;
    }
}
