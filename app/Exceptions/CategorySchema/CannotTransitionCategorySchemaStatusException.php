<?php

namespace App\Exceptions\CategorySchema;

use RuntimeException;

final class CannotTransitionCategorySchemaStatusException extends RuntimeException
{
    public static function mustBeDraft(): self
    {
        return new self('Only draft category schemas can be marked reviewed.');
    }

    public static function mustBeApproved(): self
    {
        return new self('Only approved category schemas can be archived.');
    }

    public static function mustBeArchived(): self
    {
        return new self('Only archived category schemas can be restored.');
    }

    public static function hasReviewValidationErrors(): self
    {
        return new self('Category schema cannot be marked reviewed while validation errors exist.');
    }

    public static function persistenceFailed(): self
    {
        return new self('Category schema status could not be persisted.');
    }
}
