<?php

namespace App\Exceptions\CategorySchema;

use RuntimeException;

final class CannotCloneCategorySchemaException extends RuntimeException
{
    public static function sourceAndTargetAreSame(): self
    {
        return new self('Category schema cannot be cloned into the same category.');
    }

    public static function targetSchemaIsNotEmpty(): self
    {
        return new self('Target category schema must be empty before cloning.');
    }
}
