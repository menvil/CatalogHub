<?php

namespace App\Actions\CategorySchema;

use App\Enums\CategorySchemaStatus;
use App\Exceptions\CategorySchema\CannotTransitionCategorySchemaStatusException;
use App\Models\CentralCatalog\CentralCategory;

final class MarkCategorySchemaReviewedAction
{
    public function handle(CentralCategory $category): CentralCategory
    {
        if ($category->schema_status !== CategorySchemaStatus::Draft) {
            throw CannotTransitionCategorySchemaStatusException::mustBeDraft();
        }

        if (! $category->update(['schema_status' => CategorySchemaStatus::Reviewed])) {
            throw CannotTransitionCategorySchemaStatusException::persistenceFailed();
        }

        return $category;
    }
}
