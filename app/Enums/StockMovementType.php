<?php

namespace App\Enums;

enum StockMovementType: string
{
    case StockIn     = 'stock_in';
    case StockOut    = 'stock_out';
    case TransferOut = 'transfer_out';
    case TransferIn  = 'transfer_in';

    public function isDeduction(): bool
    {
        return in_array($this, [self::StockOut, self::TransferOut]);
    }
}
