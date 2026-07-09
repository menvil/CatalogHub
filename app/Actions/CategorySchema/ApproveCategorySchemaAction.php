<?php

namespace App\Actions\CategorySchema;

use App\Enums\CategorySchemaStatus;
use App\Exceptions\CategorySchema\CannotApproveCategorySchemaException;
use App\Models\CentralCatalog\CentralCategory;
use App\Services\CategorySchema\CategorySchemaValidator;

final class ApproveCategorySchemaAction
{
    public function __construct(
        private readonly CategorySchemaValidator $validator,
    ) {}

    public function handle(CentralCategory $category): CentralCategory
    {
        if ($category->schema_status !== CategorySchemaStatus::Reviewed) {
            throw CannotApproveCategorySchemaException::mustBeReviewed();
        }

        if ($this->validator->validate($category)->hasErrors()) {
            throw CannotApproveCategorySchemaException::hasValidationErrors();
        }

        if (! $category->update(['schema_status' => CategorySchemaStatus::Approved])) {
            throw CannotApproveCategorySchemaException::persistenceFailed();
        }

        return $category;
    }
}
