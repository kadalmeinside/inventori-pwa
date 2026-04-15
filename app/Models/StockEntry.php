<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
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

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Check if the entry has enough stock for the given quantity.
     */
    public function hasSufficientStock(int $quantity): bool
    {
        return $this->quantity >= $quantity;
    }

    /**
     * Whether the current quantity is below the product's alert threshold.
     */
    public function isBelowMinStock(): bool
    {
        return $this->quantity < $this->product->min_stock;
    }
}
