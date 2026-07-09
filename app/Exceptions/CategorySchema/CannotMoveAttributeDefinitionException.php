<?php

namespace App\Exceptions\CategorySchema;

use RuntimeException;

final class CannotMoveAttributeDefinitionException extends RuntimeException
{
    public static function targetSectionBelongsToDifferentCategory(): self
    {
        return new self('Attribute definition cannot be moved to a section from another category.');
    }

    public static function invalidPosition(): self
    {
        return new self('Attribute definition position must be greater than or equal to zero.');
    }
}
