<?php

namespace App\Actions\CategorySchema;

use App\Exceptions\CategorySchema\CannotManageAttributeOptionException;
use App\Models\CentralCatalog\AttributeOption;

final class DeleteAttributeOptionAction
{
    public function handle(AttributeOption $option): void
    {
        if (! $option->attribute->data_type->allowsOptions()) {
            throw CannotManageAttributeOptionException::attributeDoesNotAllowOptions();
        }

        $option->delete();
    }
}
