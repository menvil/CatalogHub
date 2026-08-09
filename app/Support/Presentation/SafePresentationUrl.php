<?php

declare(strict_types=1);

namespace App\Support\Presentation;

final class SafePresentationUrl
{
    public static function allows(mixed $value, bool $allowQuery = false, bool $allowFragment = false): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $value = trim($value);

        if ($value === '' || str_contains($value, '\\')) {
            return false;
        }

        if ($allowQuery && str_starts_with($value, '?')) {
            return true;
        }

        if ($allowFragment && str_starts_with($value, '#')) {
            return true;
        }

        if (str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
            return true;
        }

        return in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    private function __construct() {}
}
