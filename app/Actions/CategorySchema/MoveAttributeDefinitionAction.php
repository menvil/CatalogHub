<?php

namespace App\Actions\CategorySchema;

use App\Enums\SchemaMutationOrigin;
use App\Exceptions\CategorySchema\CannotMoveAttributeDefinitionException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\User;
use App\Services\CategorySchema\SchemaRevision;
use Illuminate\Support\Facades\DB;

final class MoveAttributeDefinitionAction
{
    public function handle(AttributeDefinition $attribute, AttributeSection $targetSection, int $position, ?User $actor = null): AttributeDefinition
    {
        $categoryId = $attribute->central_category_id;

        return app(SchemaRevision::class)->mutate($categoryId, SchemaMutationOrigin::AttributeMoved, $attribute->id, fn () => $this->perform($attribute, $targetSection, $position), $actor);
    }

    private function perform(AttributeDefinition $attribute, AttributeSection $targetSection, int $position): AttributeDefinition
    {
        $attribute = AttributeDefinition::query()->findOrFail($attribute->id);
        $targetSection = AttributeSection::query()->findOrFail($targetSection->id);

        if ($attribute->central_category_id !== $targetSection->central_category_id) {
            throw CannotMoveAttributeDefinitionException::targetSectionBelongsToDifferentCategory();
        }

        if ($position < 0 || $position > AttributeDefinition::MAX_POSITION) {
            throw CannotMoveAttributeDefinitionException::invalidPosition();
        }

        DB::transaction(function () use ($attribute, $targetSection, $position): void {
            /** @var AttributeDefinition $lockedAttribute */
            $lockedAttribute = $attribute->newQuery()->whereKey($attribute->getKey())->lockForUpdate()->firstOrFail();
            $targetSection->newQuery()->whereKey($targetSection->getKey())->lockForUpdate()->firstOrFail();

            $sourceSectionId = $lockedAttribute->attribute_section_id;
            $oldPosition = $lockedAttribute->position;

            if ($sourceSectionId === $targetSection->getKey()) {
                $this->moveInsideSection($lockedAttribute, $position, $oldPosition);

                return;
            }

            if (
                AttributeDefinition::query()
                    ->where('attribute_section_id', $targetSection->getKey())
                    ->where('position', '>=', AttributeDefinition::MAX_POSITION)
                    ->exists()
            ) {
                throw CannotMoveAttributeDefinitionException::targetSectionPositionOverflow();
            }

            AttributeDefinition::query()
                ->where('central_category_id', $lockedAttribute->central_category_id)
                ->where('attribute_section_id', $sourceSectionId)
                ->where('position', '>', $oldPosition)
                ->decrement('position');

            AttributeDefinition::query()
                ->where('attribute_section_id', $targetSection->getKey())
                ->where('position', '>=', $position)
                ->increment('position');

            $lockedAttribute->update([
                'attribute_section_id' => $targetSection->getKey(),
                'position' => $position,
            ]);
        });

        /** @var AttributeDefinition $freshAttribute */
        $freshAttribute = $attribute->newQuery()->whereKey($attribute->getKey())->firstOrFail();

        return $freshAttribute;
    }

    private function moveInsideSection(AttributeDefinition $attribute, int $newPosition, int $oldPosition): void
    {
        if ($newPosition === $oldPosition) {
            return;
        }

        if ($newPosition < $oldPosition) {
            AttributeDefinition::query()
                ->where('attribute_section_id', $attribute->attribute_section_id)
                ->where('id', '!=', $attribute->getKey())
                ->whereBetween('position', [$newPosition, $oldPosition - 1])
                ->increment('position');
        } else {
            AttributeDefinition::query()
                ->where('attribute_section_id', $attribute->attribute_section_id)
                ->where('id', '!=', $attribute->getKey())
                ->whereBetween('position', [$oldPosition + 1, $newPosition])
                ->decrement('position');
        }

        $attribute->update(['position' => $newPosition]);
    }
}
