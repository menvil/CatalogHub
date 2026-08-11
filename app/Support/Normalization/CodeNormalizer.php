<?php

declare(strict_types=1);

namespace App\Support\Normalization;

use InvalidArgumentException;

final class CodeNormalizer
{
    public static function normalize(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[\s_]+/', '-', $normalized) ?? '';

        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $normalized) !== 1) {
            throw new InvalidArgumentException('A lower-case ASCII code is required.');
        }

        return $normalized;
    }
}
