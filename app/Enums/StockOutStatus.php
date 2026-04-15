<?php

namespace App\Enums;

enum StockOutStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
