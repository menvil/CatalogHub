<?php

namespace App\Services\AttributeGlobalization;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeDefinitionCrosswalk;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeOptionCrosswalk;
use App\Models\CentralCatalog\CategoryAttributeAssignment;

/** One bridge for the temporary legacy authoring paths; no consumer cutover. */
final class LegacyAttributeCompatibility
{
    /** @param array<int, array<string, mixed>> $before */
    public function synchronizeCategory(int $categoryId, array $before = []): void
    {
        app(LegacyAttributeBackfill::class)->run($categoryId);
        foreach (AttributeDefinition::query()->where('central_category_id', $categoryId)->orderBy('id')->get() as $definition) {
            $assignment = CategoryAttributeAssignment::query()->where('central_category_id', $categoryId)->where('attribute_definition_id', $definition->id)->firstOrFail();
            $assignment->fill(array_intersect_key($definition->getAttributes(), array_flip(LegacyAttributeBackfill::LOCAL_FIELDS)));
            if ($assignment->isDirty()) {
                $assignment->saveOrFail();
            }
            if ($definition->canonical_code !== null) {
                AttributeDefinitionCrosswalk::query()->where('canonical_definition_id', $definition->id)->update(['canonical_code' => $definition->canonical_code]);
            }
            $original = $before[$definition->id] ?? null;
            if ($original !== null && ($original['dimension'] !== $definition->dimension || $original['canonical_unit'] !== $definition->canonical_unit || $original['data_type'] !== $definition->data_type->value)) {
                $measurement = app(LegacyAttributeBackfill::class)->measurement($definition->dimension, $definition->canonical_unit, $definition->data_type->value);
                AttributeDefinition::query()->toBase()->where('id', $definition->id)->update(['measurement_dimension_id' => $measurement['dimension_id'], 'canonical_measurement_unit_id' => $measurement['unit_id']]);
                AttributeDefinitionCrosswalk::query()->toBase()->where('legacy_definition_id', $definition->id)->update(['measurement_status' => $measurement['status']]);
            }
        }
        foreach (AttributeOption::query()->whereIn('attribute_definition_id', AttributeDefinition::query()->where('central_category_id', $categoryId)->select('id'))->orderBy('id')->get() as $option) {
            AttributeOptionCrosswalk::query()->where('canonical_option_id', $option->id)->update(['canonical_code' => $option->code]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function snapshotCategory(int $categoryId): array
    {
        return AttributeDefinition::query()->where('central_category_id', $categoryId)->get(['id', 'dimension', 'canonical_unit', 'data_type'])->mapWithKeys(fn ($row) => [$row->id => $row->getRawOriginal()])->all();
    }

    public function mirrorAssignment(CategoryAttributeAssignment $assignment): void
    {
        // An old local reader can represent only its original Category. Never clone meaning for layout.
        AttributeDefinition::query()->toBase()->where('id', $assignment->attribute_definition_id)->where('central_category_id', $assignment->central_category_id)
            ->update(array_intersect_key($assignment->getAttributes(), array_flip(LegacyAttributeBackfill::LOCAL_FIELDS)));
    }

    /** @return list<int> */
    public function affectedCategoryIds(int $categoryId, ?int $definitionId): array
    {
        if ($definitionId === null) {
            return [$categoryId];
        }

        $ids = array_values(array_unique([$categoryId, ...CategoryAttributeAssignment::query()->where('attribute_definition_id', $definitionId)->orderBy('central_category_id')->pluck('central_category_id')->all()]));
        sort($ids, SORT_NUMERIC);

        return $ids;
    }
}
