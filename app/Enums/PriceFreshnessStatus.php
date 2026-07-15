<?php

namespace App\Enums;

enum PriceFreshnessStatus: string
{
    case Fresh = 'fresh';
    case Stale = 'stale';
    case Expired = 'expired';
    case Unknown = 'unknown';
}
