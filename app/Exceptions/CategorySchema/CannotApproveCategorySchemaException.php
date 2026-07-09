<?php

namespace App\Exceptions\CategorySchema;

use RuntimeException;

final class CannotApproveCategorySchemaException extends RuntimeException
{
    public static function hasValidationErrors(): self
    {
        return new self('Category schema cannot be approved while validation errors exist.');
    }
}
