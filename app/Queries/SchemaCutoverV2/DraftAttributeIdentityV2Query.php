<?php

namespace App\Queries\SchemaCutoverV2;

use App\Contracts\Persistence\RawSqlPersistenceBoundary;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use stdClass;

/** Frozen Phase 19.3 identity conversion. Later versions must use new classes. */
final class DraftAttributeIdentityV2Query implements RawSqlPersistenceBoundary
{
    /** @return list<array<string, mixed>> */
    public function candidates(stdClass $draft, ?int $categoryId = null): array
    {
        $categoryId ??= $draft->category_id ?? DB::connection()->table('central_products')->where('id', $draft->matched_central_product_id)->value('central_category_id');
        $version = $draft->attribute_identity_version;
        if (! in_array($version, [1, 2], true)) {
            $this->invalid('Unsupported or unreadable draft identity.');
        }
        $assignments = DB::connection()->table('category_attribute_assignments')->where('central_category_id', $categoryId)->get();
        $crosswalks = $version === 1 ? DB::connection()->table('attribute_definition_crosswalks')->where('legacy_category_id', $categoryId)->get() : collect();
        $definitionIds = $assignments->pluck('attribute_definition_id')->merge($crosswalks->pluck('canonical_definition_id'))->filter()->unique();
        $definitions = DB::connection()->table('attribute_definitions')->whereIn('id', $definitionIds)->get()->keyBy('id');
        $options = DB::connection()->table('attribute_options')->whereIn('attribute_definition_id', $definitionIds)->orderBy('position')->orderBy('id')->get()->groupBy('attribute_definition_id');
        foreach ($definitions as $definition) {
            $definition->options = $options->get($definition->id, collect());
        }
        foreach ($assignments as $assignment) {
            $assignment->definition = $definitions->get($assignment->attribute_definition_id);
        }
        $byDefinition = $assignments->keyBy('attribute_definition_id');
        $byId = $assignments->keyBy('id');
        $result = [];
        $seen = [];
        $payload = is_string($draft->attributes_json) ? json_decode($draft->attributes_json, true, 512, JSON_THROW_ON_ERROR) : $draft->attributes_json;
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
                $definition = $definitions->get($identity->canonical_definition_id);
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
            $crosswalk = DB::connection()->table('attribute_definition_crosswalks')->where('legacy_definition_id', $definition->id)->first();
            if ($crosswalk !== null && $crosswalk->canonical_definition_id !== $definition->id) {
                $this->invalid('Explicit canonical identity reconciliation required.');
            }
            $seen[$definition->id] = true;
            if (isset($candidate['value_type']) && $candidate['value_type'] !== $definition->data_type) {
                $this->invalid('Draft type conflicts with canonical meaning.');
            }
            if (in_array($definition->data_type, ['enum', 'multi_enum'], true)) {
                $codes = $definition->data_type === 'enum'
                    ? [$candidate['value_enum_code'] ?? $candidate['value'] ?? null]
                    : ($candidate['value_json'] ?? $candidate['value'] ?? []);
                $vocabulary = $definition->options->pluck('code')->all();
                if ($version === 1) {
                    $legacyIds = DB::connection()->table('attribute_definition_crosswalks')->where('canonical_definition_id', $definition->id)->pluck('legacy_definition_id');
                    $vocabulary = [...$vocabulary, ...DB::connection()->table('attribute_option_crosswalks')->whereIn('legacy_definition_id', $legacyIds)->whereNotNull('canonical_option_id')->pluck('canonical_code')->all()];
                }
                if (! is_array($codes) || array_diff($codes, $vocabulary) !== [] || count($codes) !== count(array_unique($codes))) {
                    $this->invalid('Explicit option vocabulary reconciliation required.');
                }
                $optionId = $candidate['metadata']['option_id'] ?? null;
                if ($version === 2 && $optionId !== null && ($definition->data_type !== 'enum'
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
                    $unit = DB::connection()->table('measurement_units')->where('code', $unitCode)->first();
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
    private function legacyOptions(array $candidate, stdClass $identity, stdClass $definition): array
    {
        if (! in_array($definition->data_type, ['enum', 'multi_enum'], true)) {
            return $candidate;
        }
        $rows = DB::connection()->table('attribute_option_crosswalks')->where('legacy_definition_id', $identity->legacy_definition_id)->get();
        if ($rows->count() !== $rows->pluck('legacy_code')->unique()->count()) {
            $this->invalid('Ambiguous historical option vocabulary requires explicit reconciliation.');
        }
        $maps = $rows->keyBy('legacy_code');
        $codes = $definition->data_type === 'enum'
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
            if ($definition->data_type !== 'enum' || $map === null || $map->legacy_option_id !== $optionId) {
                $this->invalid('Legacy draft option ID/code evidence conflicts.');
            }
            $candidate['metadata']['option_id'] = $map->canonical_option_id;
        }
        if ($definition->data_type === 'enum') {
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
