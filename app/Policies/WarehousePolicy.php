<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    /**
     * Super Admin can view any warehouse.
     * Branch Admin can only view their own.
     */
    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->isSuperAdmin()
            || $user->warehouse_id === $warehouse->id;
    }

    /**
     * Only Super Admin can create warehouses.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Only Super Admin can update warehouses.
     */
    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Only Super Admin can delete warehouses.
     */
    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Super Admin sees all; Branch Admin sees their own warehouse only.
     */
    public function viewAny(User $user): bool
    {
        return true; // filtered at query level based on role
    }
}
