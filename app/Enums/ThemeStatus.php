<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusHelpers;

enum ThemeStatus: string
{
    use HasStatusHelpers;

    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public static function default(): self
    {
        return self::Draft;
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Active => 'success',
            self::Archived => 'danger',
        };
    }
}
