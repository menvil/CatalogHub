<?php

namespace App\Exceptions\Themes;

use RuntimeException;
use Throwable;

final class CannotUseBlockException extends RuntimeException
{
    public static function because(string $reason, ?Throwable $previous = null): self
    {
        return new self($reason, previous: $previous);
    }
}
