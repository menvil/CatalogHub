<?php

declare(strict_types=1);

namespace App\Support\Normalization;

final class BrandInputNormalizer
{
    public static function name(string $value): string
    {
        return UnicodeNameNormalizer::display($value);
    }

    public static function nameIdentity(string $normalizedName): string
    {
        return UnicodeNameNormalizer::identity($normalizedName);
    }

    public static function nameIdentityHash(string $normalizedName): string
    {
        return hash('sha256', self::nameIdentity($normalizedName));
    }

    public static function nullableUrl(?string $value): ?string
    {
        return self::nullableText($value);
    }

    public static function nullableEmail(?string $value): ?string
    {
        return self::nullableText($value);
    }

    public static function nullableHexColor(?string $value): ?string
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
