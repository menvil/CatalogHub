<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusHelpers;

enum PriceSourceStatus: string
{
    use HasStatusHelpers;

    case Active = 'active';
    case Inactive = 'inactive';
    case Failed = 'failed';
    case Delayed = 'delayed';

    public static function default(): self
    {
        return self::Inactive;
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'gray',
            self::Failed => 'danger',
            self::Delayed => 'warning',
        };
    }
}
