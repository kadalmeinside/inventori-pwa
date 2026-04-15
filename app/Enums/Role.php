<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin  = 'super_admin';
    case BranchAdmin = 'branch_admin';

    public function label(): string
    {
        return match($this) {
            self::SuperAdmin  => 'Super Admin',
            self::BranchAdmin => 'Branch Admin',
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }
}
