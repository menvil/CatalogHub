<?php

declare(strict_types=1);

namespace App\Support\Normalization;

final class OrganizationNameNormalizer
{
    public const SEARCH_PREFIX_LENGTH = 191;

    public static function isValidInput(string $value): bool
    {
        return preg_match('/\A[^\p{Cc}]*\z/uD', $value) === 1;
    }

    public static function display(string $value): string
    {
        if (! self::isValidInput($value)) {
            return '';
        }

        return UnicodeNameNormalizer::display($value);
    }

    public static function search(string $value): string
    {
        return UnicodeNameNormalizer::identity(self::display($value));
    }

    public static function prefixForNormalizedName(string $normalizedName): string
    {
        return mb_substr($normalizedName, 0, self::SEARCH_PREFIX_LENGTH, 'UTF-8');
    }
}
