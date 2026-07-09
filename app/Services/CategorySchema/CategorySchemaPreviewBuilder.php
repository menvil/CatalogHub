<?php

namespace App\Services\CategorySchema;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;

final class CategorySchemaPreviewBuilder
{
    /**
     * @return list<array{
     *     section: string,
     *     code: string,
     *     position: int,
     *     display_style: string,
     *     is_visible: bool,
     *     attributes: list<array<string, mixed>>
     * }>
     */
    public function build(CentralCategory $category): array
    {
        $category->load([
            'attributeSections' => fn ($query) => $query->ordered(),
            'attributeSections.attributes' => fn ($query) => $query->ordered()->withCount('options'),
        ]);

        return $category->attributeSections
            ->map(fn (AttributeSection $section): array => [
                'section' => $section->name,
                'code' => $section->code,
                'position' => $section->position,
                'display_style' => $section->display_style,
                'is_visible' => $section->is_visible,
                'attributes' => $section->attributes
                    ->map(fn (AttributeDefinition $attribute): array => [
                        'name' => $attribute->name,
                        'code' => $attribute->code,
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
                        'options_count' => $attribute->options_count,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
