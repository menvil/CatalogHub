<?php

namespace App\Exceptions\Corrections;

use RuntimeException;

final class CannotApplyCorrectionException extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
