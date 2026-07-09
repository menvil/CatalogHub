<?php

namespace App\Actions\CategorySchema;

use App\Exceptions\CategorySchema\CannotMoveAttributeDefinitionException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;

final class MoveAttributeDefinitionAction
{
    public function handle(AttributeDefinition $attribute, AttributeSection $targetSection, int $position): AttributeDefinition
    {
        if ($attribute->central_category_id !== $targetSection->central_category_id) {
            throw CannotMoveAttributeDefinitionException::targetSectionBelongsToDifferentCategory();
        }

        if ($position < 0) {
            throw CannotMoveAttributeDefinitionException::invalidPosition();
        }

        $attribute->update([
            'attribute_section_id' => $targetSection->getKey(),
            'position' => $position,
        ]);

        return $attribute;
    }
}
