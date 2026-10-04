<?php

namespace App\Queries\Categories;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;

final class CategorySchemaFingerprintQuery
{
    public function forCategory(int $categoryId): string
    {
        // Bounded field allowlists describe CURRENT local schema semantics.
        // Timestamps/locale copy are excluded. These values never enter audit.
        $sections = AttributeSection::query()->where('central_category_id', $categoryId)->orderBy('id')
            ->get(['id', 'parent_id', 'code', 'name', 'position', 'display_style', 'is_collapsible', 'is_visible'])->map(fn ($row) => $row->getRawOriginal());
        $attributes = AttributeDefinition::query()->where('central_category_id', $categoryId)->orderBy('id')
            ->get(['id', 'attribute_section_id', 'code', 'name', 'data_type', 'dimension', 'canonical_unit', 'position',
                'is_required', 'is_filterable', 'is_sortable', 'is_comparable', 'is_visible', 'is_searchable'])->map(fn ($row) => $row->getRawOriginal());
        $options = AttributeOption::query()->whereIn('attribute_definition_id', $attributes->pluck('id'))->orderBy('id')
            ->get(['id', 'attribute_definition_id', 'code', 'label', 'position', 'is_visible'])->map(fn ($row) => $row->getRawOriginal());

        return hash('sha256', json_encode([$sections, $attributes, $options], JSON_THROW_ON_ERROR));
    }
}
