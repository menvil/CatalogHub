<?php

declare(strict_types=1);

namespace App\Support\Normalization;

use InvalidArgumentException;

final class LocaleNormalizer
{
    public static function normalize(string $value): string
    {
        $normalized = str_replace('_', '-', trim($value));

        if (preg_match('/\A(?<language>[a-zA-Z]{2})-(?<region>[a-zA-Z]{2})\z/', $normalized, $matches) !== 1) {
            throw new InvalidArgumentException('A language-region locale is required.');
        }

        return strtolower($matches['language']).'-'.strtoupper($matches['region']);
    }
}
