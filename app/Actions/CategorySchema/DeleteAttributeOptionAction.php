<?php

namespace App\Actions\CategorySchema;

use App\Enums\SchemaMutationOrigin;
use App\Exceptions\CategorySchema\CannotManageAttributeOptionException;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\User;
use App\Services\CategorySchema\SchemaRevision;

final class DeleteAttributeOptionAction
{
    public function handle(AttributeOption $option, ?User $actor = null): void
    {
        $categoryId = $option->attribute->central_category_id;
        app(SchemaRevision::class)->mutate($categoryId, SchemaMutationOrigin::OptionDeleted, $option->id, function () use ($option): void {
            $this->perform($option);
        }, $actor);
    }

    private function perform(AttributeOption $option): void
    {
        $option = AttributeOption::query()->findOrFail($option->id);

        if (! $option->attribute->data_type->allowsOptions()) {
            throw CannotManageAttributeOptionException::attributeDoesNotAllowOptions();
        }

        $option->delete();
    }
}
