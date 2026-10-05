<?php

namespace App\Services\AttributeGlobalization;

use App\Enums\AttributeDataType;
use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class GlobalAttributeValidation
{
    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function validate(array $data): array
    {
        $allowed = ['code', 'name', 'data_type', 'measurement_dimension_id', 'canonical_measurement_unit_id'];
        if (array_diff(array_keys($data), $allowed) !== []) {
            throw ValidationException::withMessages(['attribute' => 'Only canonical definition fields may be edited.']);
        }
        $validated = Validator::make($data, [
            'code' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/'],
            'name' => ['required', 'string', 'max:255'],
            'data_type' => ['required', Rule::enum(AttributeDataType::class)],
            'measurement_dimension_id' => ['nullable', 'integer', 'exists:measurement_dimensions,id'],
            'canonical_measurement_unit_id' => ['nullable', 'integer', 'exists:measurement_units,id'],
        ])->validate();
        $type = $validated['data_type'] instanceof AttributeDataType ? $validated['data_type']->value : $validated['data_type'];
        $dimensionId = isset($validated['measurement_dimension_id']) ? (int) $validated['measurement_dimension_id'] : null;
        $unitId = isset($validated['canonical_measurement_unit_id']) ? (int) $validated['canonical_measurement_unit_id'] : null;
        if (($dimensionId === null) !== ($unitId === null)
            || ($dimensionId !== null && (! in_array($type, ['integer', 'decimal'], true)
                || ! MeasurementUnit::query()->whereKey($unitId)->where('dimension_id', $dimensionId)->exists()))) {
            throw ValidationException::withMessages(['measurement_dimension_id' => 'Numeric measurement requires a complete compatible dimension/unit pair.']);
        }

        return [...$validated, 'data_type' => $type, 'measurement_dimension_id' => $dimensionId, 'canonical_measurement_unit_id' => $unitId,
            'dimension' => $dimensionId === null ? null : MeasurementDimension::query()->findOrFail($dimensionId)->code,
            'canonical_unit' => $unitId === null ? null : MeasurementUnit::query()->findOrFail($unitId)->code];
    }
}
