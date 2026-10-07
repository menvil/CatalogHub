<?php

namespace App\Exceptions\CategorySchema;

use RuntimeException;

final class CannotDeleteAttributeSectionException extends RuntimeException
{
    public static function hasAttributes(): self
    {
        return new self('Attribute section cannot be deleted while it has attributes.');
    }
}
