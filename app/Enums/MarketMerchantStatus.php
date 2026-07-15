<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusHelpers;

enum MarketMerchantStatus: string
{
    use HasStatusHelpers;

    case Active = 'active';
    case Inactive = 'inactive';
    case Blocked = 'blocked';

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'gray',
            self::Blocked => 'danger',
        };
    }
}
