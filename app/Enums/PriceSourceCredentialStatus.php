<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusHelpers;

enum PriceSourceCredentialStatus: string
{
    use HasStatusHelpers;

    case Active = 'active';
    case Missing = 'missing';
    case Invalid = 'invalid';
    case Rotated = 'rotated';

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Missing => 'gray',
            self::Invalid => 'danger',
            self::Rotated => 'warning',
        };
    }
}
