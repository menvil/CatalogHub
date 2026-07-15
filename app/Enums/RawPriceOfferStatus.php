<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusHelpers;

enum RawPriceOfferStatus: string
{
    use HasStatusHelpers;

    case Fetched = 'fetched';
    case Normalized = 'normalized';
    case Matched = 'matched';
    case Failed = 'failed';
    case Ignored = 'ignored';

    public function color(): string
    {
        return match ($this) {
            self::Fetched => 'gray',
            self::Normalized => 'info',
            self::Matched => 'success',
            self::Failed => 'danger',
            self::Ignored => 'warning',
        };
    }
}
