<?php

namespace App\Actions\CategorySchema;

use App\Exceptions\CategorySchema\CannotDeleteAttributeSectionException;
use App\Models\CentralCatalog\AttributeSection;

final class DeleteAttributeSectionAction
{
    public function handle(AttributeSection $section): void
    {
        if ($section->attributes()->exists()) {
            throw CannotDeleteAttributeSectionException::hasAttributes();
        }

        if ($section->children()->exists()) {
            throw CannotDeleteAttributeSectionException::hasChildren();
        }

        $section->delete();
    }
}
