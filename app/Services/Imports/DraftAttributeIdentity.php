<?php

namespace App\Services\Imports;

use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\MeasurementUnit;
use Illuminate\Validation\ValidationException;

/** Validates the initial persisted assignment/canonical identity contract. No legacy conversion. */
final class DraftAttributeIdentity
{
    /** @return list<array<string, mixed>> */
    public function candidates(NormalizedProductDraft $draft, ?int $categoryId = null): array
    {
        if ($draft->schema_version !== 1) {
            $this->invalid('Unsupported draft schema version.');
        }
        $categoryId ??= $draft->category_id ?? $draft->matchedCentralProduct?->central_category_id;
        $assignments = CategoryAttributeAssignment::query()->where('central_category_id', $categoryId)
            ->with('definition.options')->get()->keyBy('id');
        $payload = $draft->getAttribute('attributes_json');
        if (! is_array($payload) || ! array_is_list($payload)) {
            $this->invalid('Unreadable draft attribute candidate list.');
        }
        $unitCodes = [];
        foreach ($payload as $candidate) {
            if (is_array($candidate)) {
                foreach (['source_unit', 'canonical_unit'] as $field) {
                    $code = $candidate[$field] ?? $candidate['metadata'][$field] ?? null;
                    if (is_string($code)) {
                        $unitCodes[] = $code;
                    }
                }
            }
        }
        $units = MeasurementUnit::query()->whereIn('code', array_unique($unitCodes))->get()->keyBy('code');
        $seen = [];
        foreach ($payload as $candidate) {
            if (! is_array($candidate)) {
                $this->invalid('Unreadable draft attribute identity.');
            }
            $assignment = $assignments->get($candidate['category_attribute_assignment_id'] ?? null);
            if ($assignment === null || ($candidate['attribute_definition_id'] ?? null) !== $assignment->attribute_definition_id
                || ($candidate['code'] ?? null) !== $assignment->definition->code) {
                $this->invalid('Draft assignment/canonical identity does not match the resulting Product Category.');
            }
            $definition = $assignment->definition;
            if (isset($seen[$definition->id])) {
                $this->invalid('Duplicate canonical attribute candidate.');
            }
            $seen[$definition->id] = true;
            $type = $definition->data_type->value;
            if (isset($candidate['value_type']) && $candidate['value_type'] !== $type) {
                $this->invalid('Draft type conflicts with canonical meaning.');
            }
            if (in_array($type, ['enum', 'multi_enum'], true)) {
                $codes = $type === 'enum' ? [$candidate['value_enum_code'] ?? $candidate['value'] ?? null]
                    : ($candidate['value_json'] ?? $candidate['value'] ?? []);
                if (! is_array($codes) || array_diff($codes, $definition->options->pluck('code')->all()) !== []
                    || count($codes) !== count(array_unique($codes))) {
                    $this->invalid('Invalid canonical option vocabulary.');
                }
                $optionId = $candidate['metadata']['option_id'] ?? null;
                if ($optionId !== null && ($type !== 'enum'
                    || ! $definition->options->contains(fn ($option) => $option->id === $optionId && $option->code === ($codes[0] ?? null)))) {
                    $this->invalid('Draft option ID/code evidence conflicts with canonical vocabulary.');
                }
            }
            if (($candidate['measurement_dimension_id'] ?? null) !== $definition->measurement_dimension_id
                || ($candidate['canonical_measurement_unit_id'] ?? null) !== $definition->canonical_measurement_unit_id) {
                $this->invalid('Draft measurement identity does not match canonical meaning.');
            }
            foreach (['source_unit', 'canonical_unit'] as $field) {
                $unitCode = $candidate[$field] ?? $candidate['metadata'][$field] ?? null;
                if ($unitCode !== null) {
                    $unit = is_string($unitCode) ? $units->get($unitCode) : null;
                    if ($unit === null || $unit->dimension_id !== $definition->measurement_dimension_id) {
                        $this->invalid('Draft unit evidence is unknown or incompatible.');
                    }
                }
            }
        }

        return $payload;
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['attributes_json' => $message]);
    }
}
