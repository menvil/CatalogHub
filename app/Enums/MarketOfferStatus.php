<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusHelpers;

enum MarketOfferStatus: string
{
    use HasStatusHelpers;

    case Active = 'active';
    case Stale = 'stale';
    case Expired = 'expired';
    case Hidden = 'hidden';

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Stale => 'warning',
            self::Expired => 'danger',
            self::Hidden => 'gray',
        };
    }
}
