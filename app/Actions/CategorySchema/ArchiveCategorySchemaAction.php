<?php

namespace App\Actions\CategorySchema;

use App\Enums\CategorySchemaStatus;
use App\Exceptions\CategorySchema\CannotTransitionCategorySchemaStatusException;
use App\Models\CentralCatalog\CentralCategory;

final class ArchiveCategorySchemaAction
{
    public function handle(CentralCategory $category): CentralCategory
    {
        if ($category->schema_status !== CategorySchemaStatus::Approved) {
            throw CannotTransitionCategorySchemaStatusException::mustBeApproved();
        }

        if (! $category->update(['schema_status' => CategorySchemaStatus::Archived])) {
            throw CannotTransitionCategorySchemaStatusException::persistenceFailed();
        }

        return $category;
    }
}
