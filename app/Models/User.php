<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'warehouse_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'role'              => Role::class,
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    // ─── Role Helpers ─────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }

    public function isBranchAdmin(): bool
    {
        return $this->role === Role::BranchAdmin;
    }

    /**
     * Check if this user belongs to a given warehouse.
     * Super Admin always returns true.
     */
    public function belongsToWarehouse(int|Warehouse $warehouseId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $id = $warehouseId instanceof Warehouse ? $warehouseId->id : $warehouseId;

        return $this->warehouse_id == $id;
    }
}
