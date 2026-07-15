<?php

namespace App\Exceptions\Reviews;

use RuntimeException;

final class CannotCreateReviewException extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
