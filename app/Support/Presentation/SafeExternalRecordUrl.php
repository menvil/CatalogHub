<?php

declare(strict_types=1);

namespace App\Support\Presentation;

final class SafeExternalRecordUrl
{
    public static function allows(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $value = trim($value);

        if ($value === '' || mb_strlen($value) > 2048 || ! SafePresentationUrl::allows($value)) {
            return false;
        }

        $parts = parse_url($value);

        return is_array($parts)
            && isset($parts['host'])
            && ! isset($parts['user'])
            && ! isset($parts['pass']);
    }

    public static function normalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    private function __construct() {}
}
