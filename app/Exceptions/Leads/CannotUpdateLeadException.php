<?php

namespace App\Exceptions\Leads;

use RuntimeException;

final class CannotUpdateLeadException extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
