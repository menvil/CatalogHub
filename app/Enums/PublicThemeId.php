<?php

declare(strict_types=1);

namespace App\Enums;

use InvalidArgumentException;

enum PublicThemeId: string
{
    case MultiCategory = 'cataloghub-multi';
    case SingleCategory = 'cataloghub-single';

    public static function parse(string $identifier): self
    {
        return self::tryFrom($identifier)
            ?? throw new InvalidArgumentException("Unknown public theme identifier [{$identifier}].");
    }

    public function layout(): PublicLayoutType
    {
        return match ($this) {
            self::MultiCategory => PublicLayoutType::MultiCategory,
            self::SingleCategory => PublicLayoutType::SingleCategory,
        };
    }
}
