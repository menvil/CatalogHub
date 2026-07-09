<?php

namespace App\Actions\CategorySchema;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;

final class ExportCategorySchemaAction
{
    /**
     * @return array<string, mixed>
     */
    public function handle(CentralCategory $category): array
    {
        $category->load([
            'attributeSections' => fn ($query) => $query->ordered(),
            'attributeSections.attributes' => fn ($query) => $query->ordered()->with(['options' => fn ($query) => $query->ordered()]),
        ]);

        return [
            'category' => [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->name,
                'schema_status' => $category->schema_status->value,
            ],
            'sections' => $category->attributeSections
                ->map(fn (AttributeSection $section): array => [
                    'code' => $section->code,
                    'name' => $section->name,
                    'position' => $section->position,
                    'display_style' => $section->display_style,
                    'is_collapsible' => $section->is_collapsible,
                    'is_visible' => $section->is_visible,
                    'attributes' => $section->attributes
                        ->map(fn (AttributeDefinition $attribute): array => [
                            'code' => $attribute->code,
                            'name' => $attribute->name,
                            'data_type' => $attribute->data_type->value,
                            'dimension' => $attribute->dimension,
                            'canonical_unit' => $attribute->canonical_unit,
                            'position' => $attribute->position,
                            'flags' => [
                                'required' => $attribute->is_required,
                                'filterable' => $attribute->is_filterable,
                                'sortable' => $attribute->is_sortable,
                                'comparable' => $attribute->is_comparable,
                                'visible' => $attribute->is_visible,
                                'searchable' => $attribute->is_searchable,
                            ],
                            'options' => $attribute->options
                                ->map(fn (AttributeOption $option): array => [
                                    'code' => $option->code,
                                    'label' => $option->label,
                                    'position' => $option->position,
                                    'is_visible' => $option->is_visible,
                                ])
                                ->values()
                                ->all(),
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }
}
