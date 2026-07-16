<?php

namespace App\Services\Export;

use App\Models\CatalogSnapshot;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
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
                'entity_type' => 'attribute_section',
                'id' => $section->getKey(),
                'category_id' => $section->central_category_id,
                'parent_id' => $section->parent_id,
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
            yield [
                'entity_type' => 'attribute_definition',
                'id' => $definition->getKey(),
                'category_id' => $definition->central_category_id,
                'section_id' => $definition->attribute_section_id,
                'code' => $definition->code,
                'name' => $definition->name,
                'data_type' => $definition->data_type->value,
                'dimension' => $definition->dimension,
                'canonical_unit' => $definition->canonical_unit,
                'position' => $definition->position,
                'flags' => [
                    'is_required' => $definition->is_required,
                    'is_filterable' => $definition->is_filterable,
                    'is_sortable' => $definition->is_sortable,
                    'is_comparable' => $definition->is_comparable,
                    'is_visible' => $definition->is_visible,
                    'is_searchable' => $definition->is_searchable,
                ],
                'created_at' => $definition->created_at?->toISOString(),
                'updated_at' => $definition->updated_at?->toISOString(),
            ];
        }

        foreach (AttributeOption::query()->orderBy('id')->cursor() as $option) {
            yield [
                'entity_type' => 'attribute_option',
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
