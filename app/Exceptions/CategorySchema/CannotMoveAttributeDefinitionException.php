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
        return new self('Attribute definition position must be between zero and the maximum unsigned integer value.');
    }

    public static function targetSectionPositionOverflow(): self
    {
        return new self('Attribute definition cannot be moved because target section positions would exceed the maximum unsigned integer value.');
    }
}
