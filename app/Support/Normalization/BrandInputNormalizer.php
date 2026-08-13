<?php

declare(strict_types=1);

namespace App\Support\Normalization;

final class BrandInputNormalizer
{
    public static function name(string $value): string
    {
        $normalized = preg_replace('/[\p{Z}\x{0009}-\x{000D}\x{0085}]+/u', ' ', $value) ?? $value;

        return trim($normalized, ' ');
    }

    public static function nullableUrl(?string $value): ?string
    {
        return self::nullableText($value);
    }

    public static function countryCode(?string $value): ?string
    {
        $normalized = self::nullableText($value);

        return $normalized === null ? null : strtoupper($normalized);
    }

    private static function nullableText(?string $value): ?string
    {
        $normalized = $value === null ? null : trim($value);

        return $normalized === '' ? null : $normalized;
    }
}
