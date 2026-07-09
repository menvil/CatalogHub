<?php

namespace App\Actions\CategorySchema;

use App\Enums\CategorySchemaStatus;
use App\Exceptions\CategorySchema\CannotCloneCategorySchemaException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Support\Facades\DB;

final class CloneCategorySchemaAction
{
    public function handle(CentralCategory $source, CentralCategory $target): void
    {
        if ($source->is($target)) {
            throw CannotCloneCategorySchemaException::sourceAndTargetAreSame();
        }

        DB::transaction(function () use ($source, $target): void {
            /** @var CentralCategory $lockedTarget */
            $lockedTarget = $target->newQuery()->whereKey($target->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedTarget->attributeSections()->exists() || $lockedTarget->attributeDefinitions()->exists()) {
                throw CannotCloneCategorySchemaException::targetSchemaIsNotEmpty();
            }

            $source->load([
                'attributeSections' => fn ($query) => $query->ordered(),
                'attributeDefinitions' => fn ($query) => $query->ordered()->with(['options' => fn ($query) => $query->ordered()]),
            ]);

            $sectionMap = [];

            foreach ($source->attributeSections as $section) {
                $clonedSection = AttributeSection::query()->create([
                    'central_category_id' => $lockedTarget->getKey(),
                    'parent_id' => null,
                    'code' => $section->code,
                    'name' => $section->name,
                    'position' => $section->position,
                    'display_style' => $section->display_style,
                    'is_collapsible' => $section->is_collapsible,
                    'is_visible' => $section->is_visible,
                ]);

                $sectionMap[$section->getKey()] = $clonedSection;
            }

            foreach ($source->attributeSections as $section) {
                if ($section->parent_id === null) {
                    continue;
                }

                $sectionMap[$section->getKey()]->update([
                    'parent_id' => $sectionMap[$section->parent_id]->getKey(),
                ]);
            }

            foreach ($source->attributeDefinitions as $attribute) {
                $clonedAttribute = AttributeDefinition::query()->create([
                    'central_category_id' => $lockedTarget->getKey(),
                    'attribute_section_id' => $attribute->attribute_section_id === null
                        ? null
                        : $sectionMap[$attribute->attribute_section_id]->getKey(),
                    'code' => $attribute->code,
                    'name' => $attribute->name,
                    'data_type' => $attribute->data_type,
                    'dimension' => $attribute->dimension,
                    'canonical_unit' => $attribute->canonical_unit,
                    'position' => $attribute->position,
                    'is_required' => $attribute->is_required,
                    'is_filterable' => $attribute->is_filterable,
                    'is_sortable' => $attribute->is_sortable,
                    'is_comparable' => $attribute->is_comparable,
                    'is_visible' => $attribute->is_visible,
                    'is_searchable' => $attribute->is_searchable,
                ]);

                foreach ($attribute->options as $option) {
                    $clonedAttribute->options()->create([
                        'code' => $option->code,
                        'label' => $option->label,
                        'position' => $option->position,
                        'is_visible' => $option->is_visible,
                    ]);
                }
            }

            $lockedTarget->update(['schema_status' => CategorySchemaStatus::Draft]);
        });
    }
}
