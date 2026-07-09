<?php

namespace App\Actions\CategorySchema;

use App\Enums\CategorySchemaStatus;
use App\Models\CentralCatalog\CentralCategory;

final class MarkCategorySchemaReviewedAction
{
    public function handle(CentralCategory $category): CentralCategory
    {
        $category->update(['schema_status' => CategorySchemaStatus::Reviewed]);

        return $category;
    }
}
