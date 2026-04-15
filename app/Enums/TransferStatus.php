<?php

namespace App\Enums;

enum TransferStatus: string
{
    case Pending  = 'pending';
    case InTransit = 'in_transit';   // source debited, stock is "in transit"
    case Received = 'received';  // destination credited

    public function isInTransit(): bool
    {
        return $this === self::InTransit;
    }
}
