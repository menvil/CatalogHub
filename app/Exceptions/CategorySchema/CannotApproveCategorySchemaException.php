<?php

namespace App\Exceptions\CategorySchema;

use RuntimeException;

final class CannotApproveCategorySchemaException extends RuntimeException
{
    public static function hasValidationErrors(): self
    {
        return new self('Category schema cannot be approved while validation errors exist.');
    }

    public static function mustBeReviewed(): self
    {
        return new self('Only reviewed category schemas can be approved.');
    }

    public static function persistenceFailed(): self
    {
        return new self('Category schema status could not be persisted.');
    }
}
