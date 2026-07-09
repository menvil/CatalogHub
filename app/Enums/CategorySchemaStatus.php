<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusHelpers;

enum CategorySchemaStatus: string
{
    use HasStatusHelpers;

    case Draft = 'draft';
    case Reviewed = 'reviewed';
    case Approved = 'approved';
    case Archived = 'archived';

    public static function default(): self
    {
        return self::Draft;
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Reviewed => 'warning',
            self::Approved => 'success',
            self::Archived => 'danger',
        };
    }
}
