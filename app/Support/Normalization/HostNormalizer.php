<?php

declare(strict_types=1);

namespace App\Support\Normalization;

use InvalidArgumentException;

final class HostNormalizer
{
    public static function normalize(string $input): string
    {
        $input = trim($input);
        $parts = parse_url(str_contains($input, '://') ? $input : '//'.$input);
        $host = is_array($parts) ? ($parts['host'] ?? null) : null;
        $normalized = is_string($host) ? strtolower(rtrim($host, '.')) : '';

        if ($normalized === ''
            || ! str_contains($normalized, '.')
            || filter_var($normalized, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw new InvalidArgumentException('A valid site host is required.');
        }

        return $normalized;
    }
}
