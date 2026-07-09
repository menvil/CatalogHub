<?php

namespace App\Exceptions\CategorySchema;

use RuntimeException;

final class CannotManageAttributeOptionException extends RuntimeException
{
    public static function attributeDoesNotAllowOptions(): self
    {
        return new self('Attribute options can only be managed for enum or multi_enum attributes.');
    }
}
