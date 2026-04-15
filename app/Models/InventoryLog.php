<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryLog extends Model
{
    use HasFactory;

    // This is an immutable ledger — no updates allowed.
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'stock_entry_id',
        'movement_type',
        'quantity',
        'balance_before',
        'balance_after',
        'reference_id',
        'reference_type',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'movement_type'  => StockMovementType::class,
        'quantity'       => 'integer',
        'balance_before' => 'integer',
        'balance_after'  => 'integer',
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

    public function stockEntry(): BelongsTo
    {
        return $this->belongsTo(StockEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Polymorphic relation: points to StockOut or StockTransfer.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
