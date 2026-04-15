<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;

class StockTransferPolicy
{
    /**
     * Super Admin can initiate from anywhere.
     * Branch Admin can only initiate FROM their own warehouse.
     */
    public function create(User $user, Warehouse $sourceWarehouse): bool
    {
        return $user->isSuperAdmin()
            || $user->warehouse_id === $sourceWarehouse->id;
    }

    /**
     * Super Admin can view all transfers.
     * Branch Admin can see transfers where they are the source OR destination.
     */
    public function view(User $user, StockTransfer $transfer): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->warehouse_id === $transfer->source_warehouse_id
            || $user->warehouse_id === $transfer->destination_warehouse_id;
    }

    /**
     * Super Admin can receive anywhere.
     * Branch Admin can only receive INTO their own warehouse.
     */
    public function receive(User $user, StockTransfer $transfer): bool
    {
        return $user->isSuperAdmin()
            || $user->warehouse_id === $transfer->destination_warehouse_id;
    }
}
