<?php

namespace App\Policies;

use App\Models\TransferRequest;
use App\Models\User;

class TransferRequestPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isBranchAdmin();
    }

    /**
     * Determine whether the user can approve the request.
     */
    public function approve(User $user, TransferRequest $transferRequest): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can reject the request.
     */
    public function reject(User $user, TransferRequest $transferRequest): bool
    {
        return $user->isSuperAdmin();
    }
}
