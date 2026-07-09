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
        if ($this->validator->validate($category)->hasErrors()) {
            throw CannotApproveCategorySchemaException::hasValidationErrors();
        }

        $category->update(['schema_status' => CategorySchemaStatus::Approved]);

        return $category;
    }
}
