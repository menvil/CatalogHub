<?php

namespace App\Exceptions\Units;

use InvalidArgumentException;

final class CannotConvertUnitException extends InvalidArgumentException
{
    public static function unknownUnit(string $code): self
    {
        return new self("Unknown measurement unit [{$code}].");
    }

    public static function incompatible(string $from, string $to): self
    {
        return new self("Cannot convert measurement unit [{$from}] to [{$to}] because their dimensions differ.");
    }
}
