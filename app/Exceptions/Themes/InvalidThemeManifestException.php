<?php

namespace App\Exceptions\Themes;

use InvalidArgumentException;
use Throwable;

final class InvalidThemeManifestException extends InvalidArgumentException
{
    public static function because(string $reason, ?Throwable $previous = null): self
    {
        return new self($reason, previous: $previous);
    }
}
