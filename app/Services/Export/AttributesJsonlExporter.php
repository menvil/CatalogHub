<?php

namespace App\Services\Export;

use App\Models\CatalogSnapshot;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use Generator;

final class AttributesJsonlExporter implements JsonlExporter
{
    public function __construct(private readonly JsonlStreamWriter $writer) {}

    public function export(CatalogSnapshot $snapshot): JsonlExportResult
    {
        return $this->writer->write($snapshot, 'attributes', $this->rows());
    }

    /** @return Generator<int, array<string, mixed>> */
    private function rows(): Generator
    {
        foreach (AttributeSection::query()->orderBy('id')->cursor() as $section) {
            yield [
                'schema_version' => 1, 'entity_type' => 'attribute_section',
                'id' => $section->getKey(),
                'category_id' => $section->central_category_id,
                'code' => $section->code,
                'name' => $section->name,
                'position' => $section->position,
                'display_style' => $section->display_style,
                'flags' => [
                    'is_collapsible' => $section->is_collapsible,
                    'is_visible' => $section->is_visible,
                ],
                'created_at' => $section->created_at?->toISOString(),
                'updated_at' => $section->updated_at?->toISOString(),
            ];
        }

        foreach (AttributeDefinition::query()->orderBy('id')->cursor() as $definition) {
            yield ['schema_version' => 1, 'entity_type' => 'attribute_definition',
                ...$definition->only(['id', 'code', 'name', 'data_type', 'measurement_dimension_id', 'canonical_measurement_unit_id', 'created_at', 'updated_at'])];
        }
        foreach (CategoryAttributeAssignment::query()->orderBy('id')->cursor() as $assignment) {
            yield ['schema_version' => 1, 'entity_type' => 'category_attribute_assignment',
                ...$assignment->only(['id', 'central_category_id', 'attribute_definition_id', 'attribute_section_id', 'position', 'is_required', 'is_visible', 'is_searchable', 'is_sortable', 'created_at', 'updated_at'])];
        }

        foreach (AttributeOption::query()->orderBy('id')->cursor() as $option) {
            yield [
                'schema_version' => 1, 'entity_type' => 'attribute_option',
                'id' => $option->getKey(),
                'attribute_definition_id' => $option->attribute_definition_id,
                'code' => $option->code,
                'label' => $option->label,
                'position' => $option->position,
                'flags' => ['is_visible' => $option->is_visible],
                'created_at' => $option->created_at?->toISOString(),
                'updated_at' => $option->updated_at?->toISOString(),
            ];
        }
    }
}
