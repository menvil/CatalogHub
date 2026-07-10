<?php

namespace App\Exceptions\ProductAttributes;

use RuntimeException;

final class CannotSaveProductSpecsException extends RuntimeException
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
