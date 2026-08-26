<?php

declare(strict_types=1);

namespace App\Support\Validation;

final class CentralBrandProfileConstraints
{
    public const MIN_FOUNDED_YEAR = 1000;

    public const URL_MAX_LENGTH = 255;

    public const EMAIL_MAX_LENGTH = 254;

    public const HEX_COLOR_PATTERN = '/\A#[0-9A-Fa-f]{6}\z/';

    public static function maximumFoundedYear(): int
    {
        return (int) now()->year;
    }

    private function __construct() {}
}
