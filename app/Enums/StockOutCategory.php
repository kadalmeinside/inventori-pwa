<?php

namespace App\Enums;

enum StockOutCategory: string
{
    case Sales = 'sales';
    case InternalUse = 'internal_use';
    case Damaged = 'damaged';
    case Expired = 'expired';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match($this) {
            self::Sales       => 'Penjualan',
            self::InternalUse => 'Pemakaian Internal',
            self::Damaged     => 'Barang Rusak',
            self::Expired     => 'Kedaluwarsa',
            self::Adjustment  => 'Penyesuaian',
        };
    }
}
