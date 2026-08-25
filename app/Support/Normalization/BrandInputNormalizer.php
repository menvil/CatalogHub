<?php

declare(strict_types=1);

namespace App\Support\Normalization;

use Normalizer;

final class BrandInputNormalizer
{
    public static function name(string $value): string
    {
        $normalized = preg_replace('/[\p{Z}\x{0009}-\x{000D}\x{0085}]+/u', ' ', $value) ?? $value;

        return self::normalizeUnicode(trim($normalized, ' '));
    }

    public static function nameIdentity(string $normalizedName): string
    {
        return self::normalizeUnicode(mb_convert_case($normalizedName, MB_CASE_FOLD, 'UTF-8'));
    }

    public static function nameIdentityHash(string $normalizedName): string
    {
        return hash('sha256', self::nameIdentity($normalizedName));
    }

    public static function nullableUrl(?string $value): ?string
    {
        return self::nullableText($value);
    }

    private static function nullableText(?string $value): ?string
    {
        $normalized = $value === null ? null : trim($value);

        return $normalized === '' ? null : $normalized;
    }

    private static function normalizeUnicode(string $value): string
    {
        return Normalizer::normalize($value, Normalizer::FORM_C) ?: $value;
    }
}
