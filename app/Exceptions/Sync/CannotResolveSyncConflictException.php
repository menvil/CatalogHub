<?php

namespace App\Exceptions\Sync;

use RuntimeException;

final class CannotResolveSyncConflictException extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
