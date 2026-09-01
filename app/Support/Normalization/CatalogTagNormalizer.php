<?php

declare(strict_types=1);

namespace App\Support\Normalization;

final class CatalogTagNormalizer
{
    public static function name(string $value): string
    {
        return UnicodeNameNormalizer::display($value);
    }

    public static function identity(string $normalizedName): string
    {
        return UnicodeNameNormalizer::identity($normalizedName);
    }

    public static function identityHash(string $normalizedName): string
    {
        return hash('sha256', self::identity($normalizedName));
    }
}
