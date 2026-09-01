<?php

declare(strict_types=1);

namespace App\Support\Normalization;

use Normalizer;

final class UnicodeNameNormalizer
{
    public static function display(string $value): string
    {
        $normalized = preg_replace('/[\p{Z}\x{0009}-\x{000D}\x{0085}]+/u', ' ', $value);
        if (! is_string($normalized)) {
            return '';
        }

        return self::normalizeUnicode(trim($normalized, ' '));
    }

    public static function identity(string $normalizedName): string
    {
        if (preg_match('//u', $normalizedName) !== 1) {
            return '';
        }

        return self::normalizeUnicode(mb_convert_case($normalizedName, MB_CASE_FOLD, 'UTF-8'));
    }

    private static function normalizeUnicode(string $value): string
    {
        $normalized = Normalizer::normalize($value, Normalizer::FORM_C);

        return is_string($normalized) ? $normalized : '';
    }
}
