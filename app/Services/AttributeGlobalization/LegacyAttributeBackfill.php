<?php

namespace App\Services\AttributeGlobalization;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeDefinitionCrosswalk;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeOptionCrosswalk;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\Imports\AttributeMapping;
use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Additive, repeatable identity backfill. Never chooses a semantic merge. */
final class LegacyAttributeBackfill
{
    public const LOCAL_FIELDS = ['attribute_section_id', 'position', 'is_required', 'is_visible', 'is_searchable', 'is_sortable'];

    public function run(?int $categoryId = null): void
    {
        DB::transaction(function () use ($categoryId): void {
            foreach (AttributeDefinition::query()->toBase()->whereNotNull('central_category_id')->when($categoryId !== null, fn ($q) => $q->where('central_category_id', $categoryId))->orderBy('id')->cursor() as $definition) {
                $row = (array) $definition;
                CategoryAttributeAssignment::query()->toBase()->insertOrIgnore([
                    'central_category_id' => $row['central_category_id'], 'attribute_definition_id' => $row['id'],
                    ...array_intersect_key($row, array_flip(self::LOCAL_FIELDS)),
                    'created_at' => $row['created_at'], 'updated_at' => $row['updated_at'],
                ]);
                $duplicate = AttributeDefinition::query()->toBase()->where('code', $row['code'])->where('id', '!=', $row['id'])->exists();
                if ($duplicate) {
                    if (AttributeDefinition::query()->where('code', $row['code'])->whereNull('central_category_id')->exists()) {
                        throw ValidationException::withMessages(['code' => 'Code is reserved by a global canonical definition.']);
                    }
                    // A later legacy clone must not turn a provisional singleton into an approved winner.
                    $provisionalIds = AttributeDefinitionCrosswalk::query()->where('legacy_code', $row['code'])->where('identity_status', 'identity_preserved')->pluck('legacy_definition_id');
                    AttributeDefinition::query()->whereIn('id', $provisionalIds)->where('code', $row['code'])->update(['canonical_code' => null]);
                    AttributeDefinitionCrosswalk::query()->whereIn('legacy_definition_id', $provisionalIds)->update([
                        'canonical_definition_id' => null, 'canonical_code' => null, 'identity_status' => 'unresolved_code_collision',
                        'reason' => 'Explicit canonical selection or distinct approved code required.',
                    ]);
                }
                $measurement = $this->measurement($row['dimension'], $row['canonical_unit'], $row['data_type']);
                // Existing crosswalks are immutable inventory; reviewed reconciliation is separate.
                if (! AttributeDefinitionCrosswalk::query()->toBase()->where('legacy_definition_id', $row['id'])->exists()) {
                    AttributeDefinition::query()->toBase()->where('id', $row['id'])->update([
                        'canonical_code' => $duplicate ? null : $row['code'],
                        'measurement_dimension_id' => $measurement['dimension_id'],
                        'canonical_measurement_unit_id' => $measurement['unit_id'],
                    ]);
                    AttributeDefinitionCrosswalk::query()->toBase()->insert([
                        'legacy_definition_id' => $row['id'], 'legacy_category_id' => $row['central_category_id'], 'legacy_code' => $row['code'],
                        'canonical_definition_id' => $duplicate ? null : $row['id'], 'canonical_code' => $duplicate ? null : $row['code'],
                        'identity_status' => $duplicate ? 'unresolved_code_collision' : 'identity_preserved',
                        'measurement_status' => $measurement['status'], 'reason' => $duplicate ? 'Explicit canonical selection or distinct approved code required.' : null,
                    ]);
                }
            }
            foreach (AttributeOption::query()->toBase()->when($categoryId !== null, fn ($q) => $q->whereIn('attribute_definition_id', AttributeDefinition::query()->where('central_category_id', $categoryId)->select('id')))->orderBy('id')->cursor() as $option) {
                AttributeOptionCrosswalk::query()->toBase()->insertOrIgnore([
                    'legacy_option_id' => $option->id, 'legacy_definition_id' => $option->attribute_definition_id, 'legacy_code' => $option->code,
                    'canonical_option_id' => $option->id, 'canonical_code' => $option->code, 'status' => 'identity_preserved',
                ]);
            }
            foreach (AttributeMapping::query()->toBase()->whereNotNull('attribute_definition_id')->when($categoryId !== null, fn ($q) => $q->where('category_id', $categoryId))->whereNull('category_attribute_assignment_id')->orderBy('id')->cursor() as $mapping) {
                $id = CategoryAttributeAssignment::query()->toBase()->where('central_category_id', $mapping->category_id)->where('attribute_definition_id', $mapping->attribute_definition_id)->value('id');
                if ($id !== null) {
                    AttributeMapping::query()->toBase()->where('id', $mapping->id)->update(['category_attribute_assignment_id' => $id]);
                }
            }
        });
    }

    /** @return array{dimension_id: int|null, unit_id: int|null, status: string} */
    public function measurement(?string $dimension, ?string $unit, string $type): array
    {
        $status = 'unmeasured';
        $dimensionId = null;
        $unitId = null;
        if ($dimension !== null || $unit !== null) {
            $dimensionRow = MeasurementDimension::query()->toBase()->where('code', $dimension)->first();
            $unitRow = MeasurementUnit::query()->toBase()->where('code', $unit)->first();
            $status = match (true) {
                $dimension === null || $unit === null => 'incomplete_pair',
                $dimensionRow === null || $unitRow === null => 'unknown_catalog_code',
                (int) $unitRow->dimension_id !== (int) $dimensionRow->id => 'incompatible_pair',
                ! in_array($type, ['integer', 'decimal'], true) => 'measured_non_numeric',
                default => 'resolved_exact',
            };
            if ($status === 'resolved_exact') {
                $dimensionId = (int) $dimensionRow->id;
                $unitId = (int) $unitRow->id;
            }
        }

        return ['dimension_id' => $dimensionId, 'unit_id' => $unitId, 'status' => $status];
    }
}
