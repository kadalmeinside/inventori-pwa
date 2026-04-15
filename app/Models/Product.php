<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'category',
        'unit',
        'min_stock',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'min_stock'  => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function stockEntries(): HasMany
    {
        return $this->hasMany(StockEntry::class);
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }
}
