<?php

declare(strict_types=1);

namespace App\Support\Normalization;

use Normalizer;

final class CatalogTagNormalizer
{
    public static function name(string $value): string
    {
        $normalized = preg_replace('/[\p{Z}\x{0009}-\x{000D}\x{0085}]+/u', ' ', $value) ?? $value;

        return self::normalizeUnicode(trim($normalized, ' '));
    }

    public static function identity(string $normalizedName): string
    {
        return self::normalizeUnicode(mb_convert_case($normalizedName, MB_CASE_FOLD, 'UTF-8'));
    }

    public static function identityHash(string $normalizedName): string
    {
        return hash('sha256', self::identity($normalizedName));
    }

    private static function normalizeUnicode(string $value): string
    {
        return Normalizer::normalize($value, Normalizer::FORM_C) ?: $value;
    }
}
