<?php

namespace App\Exceptions\Themes;

use RuntimeException;

final class CannotUseBlockException extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
