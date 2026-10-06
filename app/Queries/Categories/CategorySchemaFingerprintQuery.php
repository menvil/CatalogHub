<?php

namespace App\Queries\Categories;

use App\Models\AttributeDisplayRule;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CategoryComparisonAttribute;
use App\Models\FacetDefinition;
use App\Models\FacetOption;

final class CategorySchemaFingerprintQuery
{
    public function forCategory(int $categoryId): string
    {
        // Bounded field allowlists describe CURRENT local schema semantics.
        // Timestamps/locale copy are excluded. These values never enter audit.
        // Raw driver values are compared within one transaction/connection only;
        // this is not a persisted hash stable across databases or connections.
        $sections = AttributeSection::query()->where('central_category_id', $categoryId)->orderBy('id')
            ->get(['id', 'parent_id', 'code', 'name', 'position', 'display_style', 'is_collapsible', 'is_visible'])->map(fn ($row) => $row->getRawOriginal());
        $assignments = CategoryAttributeAssignment::query()->where('central_category_id', $categoryId)->orderBy('id')
            ->get(['id', 'attribute_definition_id', 'attribute_section_id', 'position', 'is_required', 'is_visible', 'is_searchable', 'is_sortable'])->map(fn ($row) => $row->getRawOriginal());
        $global = AttributeDefinition::query()->whereIn('id', $assignments->pluck('attribute_definition_id'))->orderBy('id')
            ->get(['id', 'code', 'name', 'data_type', 'measurement_dimension_id', 'canonical_measurement_unit_id'])->map(fn ($row) => $row->getRawOriginal());
        $options = AttributeOption::query()->whereIn('attribute_definition_id', $assignments->pluck('attribute_definition_id'))->orderBy('id')
            ->get(['id', 'attribute_definition_id', 'code', 'label', 'position', 'is_visible'])->map(fn ($row) => $row->getRawOriginal());

        return hash('sha256', json_encode([$sections, $assignments, $global, $options,
            AttributeDisplayRule::query()->whereIn('attribute_definition_id', $assignments->pluck('attribute_definition_id'))->orderBy('id')->get()->map(fn ($row) => $row->except(['created_at', 'updated_at'])),
            FacetOption::query()->whereIn('facet_definition_id', FacetDefinition::query()->where('category_id', $categoryId)->select('id'))->orderBy('id')->get()->map(fn ($row) => $row->except(['created_at', 'updated_at'])),
            FacetDefinition::query()->where('category_id', $categoryId)->orderBy('id')->get()->map(fn ($row) => $row->except(['created_at', 'updated_at'])),
            CategoryComparisonAttribute::query()->where('central_category_id', $categoryId)->orderBy('id')->get(['id', 'category_attribute_assignment_id', 'position', 'is_visible'])], JSON_THROW_ON_ERROR));
    }
}
