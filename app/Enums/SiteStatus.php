<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusHelpers;

enum SiteStatus: string
{
    use HasStatusHelpers;

    case Draft = 'draft';
    case Active = 'active';
    case Suspended = 'suspended';
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
            self::Suspended => 'warning',
            self::Archived => 'danger',
        };
    }

    public function isPubliclyAvailable(): bool
    {
        return $this === self::Active;
    }

    public function allowsAdministration(): bool
    {
        return $this !== self::Archived;
    }
}
