<?php

namespace App\Services\AttributeGlobalization;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeDefinitionCrosswalk;
use App\Models\CentralCatalog\AttributeOptionCrosswalk;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\MeasurementUnit;
use Illuminate\Validation\ValidationException;

/** Explicit historical v1 resolver; target writers never fall back to code lookup. */
final class DraftAttributeIdentityV2
{
    /** @return list<array<string, mixed>> */
    public function candidates(NormalizedProductDraft $draft, ?int $categoryId = null): array
    {
        $categoryId ??= $draft->category_id ?? CentralProduct::query()->whereKey($draft->matched_central_product_id)->value('central_category_id');
        $version = $draft->attribute_identity_version;
        if (! in_array($version, [1, 2], true)) {
            $this->invalid('Unsupported or unreadable draft identity.');
        }
        $assignments = CategoryAttributeAssignment::query()->with(['definition.options', 'definition.canonicalMeasurementUnit'])
            ->where('central_category_id', $categoryId)->get();
        $byDefinition = $assignments->keyBy('attribute_definition_id');
        $byId = $assignments->keyBy('id');
        $crosswalks = $version === 1 ? AttributeDefinitionCrosswalk::query()->where('legacy_category_id', $categoryId)->get() : collect();
        $result = [];
        $seen = [];
        $payload = $draft->getAttribute('attributes_json');
        if (! is_array($payload) || ! array_is_list($payload)) {
            $this->invalid('Unreadable draft attribute candidate list.');
        }
        foreach ($payload as $candidate) {
            if (! is_array($candidate)) {
                $this->invalid('Unreadable draft attribute identity.');
            }
            if ($version === 1) {
                $matches = isset($candidate['attribute_definition_id'])
                    ? $crosswalks->where('legacy_definition_id', (int) $candidate['attribute_definition_id'])
                    : $crosswalks->where('legacy_code', $candidate['code'] ?? null);
                if ($matches->count() !== 1 || $matches->first()->canonical_definition_id === null) {
                    $this->invalid('Explicit draft identity reconciliation required.');
                }
                $identity = $matches->first();
                if (isset($candidate['code']) && ! in_array($candidate['code'], [$identity->legacy_code, $identity->canonical_code], true)) {
                    $this->invalid('Legacy draft ID/code evidence conflicts.');
                }
                $assignment = $byDefinition->get($identity->canonical_definition_id) ?? $byDefinition->get($identity->legacy_definition_id);
                $definition = AttributeDefinition::query()->with('options')->find($identity->canonical_definition_id);
                if ($definition === null) {
                    $this->invalid('Missing retained canonical definition.');
                }
                $candidate = $this->legacyOptions($candidate, $identity, $definition);
            } else {
                $assignment = $byId->get($candidate['category_attribute_assignment_id'] ?? null);
                if ($assignment === null || (int) ($candidate['attribute_definition_id'] ?? 0) !== $assignment->attribute_definition_id
                    || ($candidate['code'] ?? null) !== $assignment->definition->code) {
                    $this->invalid('Draft assignment/canonical identity does not match the resulting Product Category.');
                }
            }
            $definition = $version === 1 ? $definition : $assignment->definition;
            if ($assignment === null || $definition === null || isset($seen[$definition->id])) {
                $this->invalid('Missing assignment or canonical draft merge collision.');
            }
            app(AttributeIdentityReservation::class)->assertResolved($definition, 'attributes_json');
            $seen[$definition->id] = true;
            if (isset($candidate['value_type']) && $candidate['value_type'] !== $definition->data_type->value) {
                $this->invalid('Draft type conflicts with canonical meaning.');
            }
            if ($definition->data_type->allowsOptions()) {
                $codes = $definition->data_type->value === 'enum'
                    ? [$candidate['value_enum_code'] ?? $candidate['value'] ?? null]
                    : ($candidate['value_json'] ?? $candidate['value'] ?? []);
                $vocabulary = $definition->options->pluck('code')->all();
                if ($version === 1) {
                    $legacyIds = AttributeDefinitionCrosswalk::query()->where('canonical_definition_id', $definition->id)->pluck('legacy_definition_id');
                    $vocabulary = [...$vocabulary, ...AttributeOptionCrosswalk::query()->whereIn('legacy_definition_id', $legacyIds)->whereNotNull('canonical_option_id')->pluck('canonical_code')->all()];
                }
                if (! is_array($codes) || array_diff($codes, $vocabulary) !== [] || count($codes) !== count(array_unique($codes))) {
                    $this->invalid('Explicit option vocabulary reconciliation required.');
                }
                $optionId = $candidate['metadata']['option_id'] ?? null;
                if ($version === 2 && $optionId !== null && ($definition->data_type->value !== 'enum'
                    || ! $definition->options->contains(fn ($option) => $option->id === $optionId && $option->code === ($codes[0] ?? null)))) {
                    $this->invalid('Draft option ID/code evidence conflicts with canonical vocabulary.');
                }
            }
            if ($version === 2 && (($candidate['measurement_dimension_id'] ?? null) !== $definition->measurement_dimension_id
                || ($candidate['canonical_measurement_unit_id'] ?? null) !== $definition->canonical_measurement_unit_id)) {
                $this->invalid('Draft measurement identity does not match canonical meaning.');
            }
            foreach (['source_unit', 'canonical_unit'] as $field) {
                $unitCode = $candidate[$field] ?? $candidate['metadata'][$field] ?? null;
                if ($unitCode !== null) {
                    $unit = MeasurementUnit::query()->where('code', $unitCode)->first();
                    if ($unit === null || $unit->dimension_id !== $definition->measurement_dimension_id) {
                        $this->invalid('Draft unit evidence is unknown or incompatible.');
                    }
                }
            }
            $result[] = [...$candidate, 'category_attribute_assignment_id' => $assignment->id,
                'attribute_definition_id' => $definition->id, 'code' => $definition->code,
                'measurement_dimension_id' => $definition->measurement_dimension_id, 'canonical_measurement_unit_id' => $definition->canonical_measurement_unit_id];
        }

        return $result;
    }

    /** Explicit option evidence; no live-code fallback may reinterpret legacy payloads. */
    private function legacyOptions(array $candidate, AttributeDefinitionCrosswalk $identity, AttributeDefinition $definition): array
    {
        if (! $definition->data_type->allowsOptions()) {
            return $candidate;
        }
        $rows = AttributeOptionCrosswalk::query()->where('legacy_definition_id', $identity->legacy_definition_id)->get();
        if ($rows->count() !== $rows->pluck('legacy_code')->unique()->count()) {
            $this->invalid('Ambiguous historical option vocabulary requires explicit reconciliation.');
        }
        $maps = $rows->keyBy('legacy_code');
        $codes = $definition->data_type->value === 'enum'
            ? [$candidate['value_enum_code'] ?? $candidate['value'] ?? null]
            : ($candidate['value_json'] ?? $candidate['value'] ?? []);
        if (! is_array($codes)) {
            $this->invalid('Invalid legacy option value shape.');
        }
        $converted = [];
        foreach ($codes as $code) {
            $map = $maps->get($code);
            if ($map === null || $map->canonical_option_id === null || ! in_array($map->status, ['identity_preserved', 'reconciled_explicit'], true)) {
                $this->invalid('Explicit legacy option reconciliation required.');
            }
            $converted[] = $map->canonical_code;
        }
        if (count(array_unique($converted)) !== count($converted)) {
            $this->invalid('Option mapping would collapse a multi-enum fact.');
        }
        $optionId = $candidate['metadata']['option_id'] ?? null;
        if ($optionId !== null) {
            $map = $maps->get($codes[0] ?? null);
            if ($definition->data_type->value !== 'enum' || $map === null || $map->legacy_option_id !== $optionId) {
                $this->invalid('Legacy draft option ID/code evidence conflicts.');
            }
            $candidate['metadata']['option_id'] = $map->canonical_option_id;
        }
        if ($definition->data_type->value === 'enum') {
            $candidate['value_enum_code'] = $converted[0] ?? null;
            if (array_key_exists('value', $candidate)) {
                $candidate['value'] = $converted[0] ?? null;
            }
        } else {
            $candidate['value_json'] = $converted;
            if (array_key_exists('value', $candidate)) {
                $candidate['value'] = $converted;
            }
        }

        return $candidate;
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['attributes_json' => $message]);
    }
}
