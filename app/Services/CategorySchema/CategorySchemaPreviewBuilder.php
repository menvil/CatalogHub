<?php

namespace App\Services\CategorySchema;

use App\Models\CentralCatalog\CentralCategory;

final class CategorySchemaPreviewBuilder
{
    /** @return list<array<string, mixed>> */
    public function build(CentralCategory $category): array
    {
        $category->load(['attributeSections' => fn ($q) => $q->ordered(),
            'attributeAssignments' => fn ($q) => $q->ordered()->with(['definition' => fn ($q) => $q->withCount('options'), 'definition.measurementDimension', 'definition.canonicalMeasurementUnit'])]);
        $sections = [];
        foreach ($category->attributeSections as $section) {
            $sections[$section->id] = ['section' => $section->name, 'code' => $section->code, 'position' => $section->position,
                'display_style' => $section->display_style, 'is_visible' => $section->is_visible, 'attributes' => []];
        }
        foreach ($category->attributeAssignments as $assignment) {
            $key = $assignment->attribute_section_id ?? 'ungrouped';
            $sections[$key] ??= ['section' => 'Ungrouped', 'code' => 'ungrouped', 'position' => PHP_INT_MAX, 'display_style' => 'table', 'is_visible' => true, 'attributes' => []];
            $d = $assignment->definition;
            $sections[$key]['attributes'][] = ['assignment_id' => $assignment->id, 'definition_id' => $d->id,
                'name' => $d->name, 'code' => $d->code, 'data_type' => $d->data_type->value,
                'measurement_dimension_id' => $d->measurement_dimension_id, 'canonical_measurement_unit_id' => $d->canonical_measurement_unit_id,
                'dimension' => $d->measurementDimension?->code, 'canonical_unit' => $d->canonicalMeasurementUnit?->code,
                'position' => $assignment->position, 'flags' => ['required' => $assignment->is_required, 'visible' => $assignment->is_visible,
                    'searchable' => $assignment->is_searchable, 'sortable' => $assignment->is_sortable], 'options_count' => $d->options_count];
        }

        return array_values($sections);
    }
}
