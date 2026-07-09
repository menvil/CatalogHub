<?php

namespace App\Exceptions\Units;

use InvalidArgumentException;

final class CannotParseUnitException extends InvalidArgumentException
{
    public static function invalidValue(string $raw): self
    {
        return new self("Cannot parse measured value [{$raw}].");
    }

    public static function unknownUnit(string $rawUnit): self
    {
        return new self("Cannot parse unknown measurement unit [{$rawUnit}].");
    }
}
