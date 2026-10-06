<?php

namespace App\Queries\Attributes;

use App\Contracts\Persistence\RawSqlPersistenceBoundary;
use App\Models\CentralCatalog\AttributeDefinition;
use Illuminate\Database\Query\Builder;

/** Checks explicit target selections; matching vocabulary never selects a target. */
final class CanonicalMergePreflightV2Query implements RawSqlPersistenceBoundary
{
    /** @return array<string, list<mixed>> */
    public function blockers(): array
    {
        $issues = [];
        $definitions = $this->table('attribute_definitions')->get()->keyBy('id');
        $targets = $this->table('attribute_definition_crosswalks')->pluck('canonical_definition_id', 'legacy_definition_id');
        $options = $this->table('attribute_options')->orderBy('id')->get()->keyBy('id');
        $optionMaps = $this->table('attribute_option_crosswalks')->orderBy('legacy_option_id')->get()->keyBy('legacy_option_id');
        $translations = $this->table('attribute_translations')->get()->groupBy('attribute_definition_id');
        $optionTranslations = $this->table('attribute_option_translations')->get()->groupBy('attribute_option_id');
        $rules = $this->table('attribute_display_rules')->get()->groupBy('attribute_definition_id');
        $futureOptions = [];
        foreach ($options as $option) {
            $canonicalId = $targets->get($option->attribute_definition_id) ?? $option->attribute_definition_id;
            $targetOption = $options->get($optionMaps->has($option->id) ? $optionMaps->get($option->id)->canonical_option_id : $option->id);
            if ($targetOption !== null) {
                $futureOptions[$canonicalId.':'.$targetOption->code][$targetOption->id] = true;
            }
        }
        foreach ($futureOptions as $key => $ids) {
            if (count($ids) > 1) {
                $issues['option_code_conflicts'][] = ['identity' => $key, 'option_ids' => array_keys($ids)];
            }
        }
        foreach ($targets as $legacyId => $canonicalId) {
            if ($canonicalId === null || $canonicalId === $legacyId || ! $definitions->has($legacyId)) {
                continue;
            }
            $source = $definitions->get($legacyId);
            $target = $definitions->get($canonicalId);
            if ($target === null || [$source->data_type, $source->measurement_dimension_id, $source->canonical_measurement_unit_id]
                !== [$target->data_type, $target->measurement_dimension_id, $target->canonical_measurement_unit_id]) {
                $issues['incompatible_canonical_meanings'][] = [$legacyId, $canonicalId];

                continue;
            }
            $targetLocales = $translations->get($canonicalId, collect())->pluck('locale_id')->all();
            foreach ($translations->get($legacyId, collect()) as $translation) {
                if (in_array($translation->locale_id, $targetLocales, true)) {
                    $issues['translation_merge_conflicts'][] = ['translation_id' => $translation->id, 'target_definition_id' => $canonicalId, 'locale_id' => $translation->locale_id];
                }
            }
            $targetScopes = $rules->get($canonicalId, collect())->keyBy(fn ($rule) => $rule->market_code.':'.$rule->locale);
            foreach ($rules->get($legacyId, collect()) as $rule) {
                if ($targetScopes->has($rule->market_code.':'.$rule->locale)) {
                    $issues['display_rule_conflicts'][] = ['rule_id' => $rule->id, 'target_definition_id' => $canonicalId];
                }
            }
            foreach ($options->where('attribute_definition_id', $legacyId) as $option) {
                $map = $optionMaps->get($option->id);
                $targetOption = $options->get($map?->canonical_option_id);
                if ($map === null || $map->status !== 'reconciled_explicit' || $targetOption === null
                    || ($targets->get($targetOption->attribute_definition_id) ?? $targetOption->attribute_definition_id) !== $canonicalId) {
                    $issues['unresolved_option_maps'][] = $option->id;

                    continue;
                }
                if ($targetOption->id !== $option->id) {
                    $locales = $optionTranslations->get($targetOption->id, collect())->pluck('locale_id')->all();
                    foreach ($optionTranslations->get($option->id, collect()) as $translation) {
                        if (in_array($translation->locale_id, $locales, true)) {
                            $issues['option_translation_merge_conflicts'][] = ['translation_id' => $translation->id, 'target_option_id' => $targetOption->id];
                        }
                    }
                }
            }
        }
        // Check the complete future owner namespace, including collisions between two sources.
        foreach ([['attribute_translations', 'attribute_definition_id', ['locale_id'], $targets],
            ['attribute_display_rules', 'attribute_definition_id', ['market_code', 'locale'], $targets],
            ['attribute_option_translations', 'attribute_option_id', ['locale_id'], $optionMaps->pluck('canonical_option_id', 'legacy_option_id')]] as [$table, $owner, $keys, $map]) {
            $groups = [];
            foreach ($this->table($table)->orderBy('id')->cursor() as $row) {
                $key = json_encode([$map->get($row->$owner) ?? $row->$owner, ...array_map(fn ($field) => $row->$field, $keys)], JSON_THROW_ON_ERROR);
                $groups[$key][] = $row->id;
            }
            foreach ($groups as $ids) {
                if (count($ids) > 1) {
                    $issues['future_owner_conflicts'][] = ['table' => $table, 'row_ids' => $ids];
                }
            }
        }
        foreach ($optionMaps as $map) {
            $targetMap = $optionMaps->get($map->canonical_option_id);
            if ($targetMap !== null && $targetMap->canonical_option_id !== $map->canonical_option_id) {
                $issues['option_target_ambiguities'][] = $map->legacy_option_id;
            }
        }
        $codesByDefinition = $optionMaps->groupBy('legacy_definition_id')->map(fn ($maps) => $maps->keyBy('legacy_code'));
        foreach ($this->table('central_product_attribute_values')->whereIn('value_type', ['enum', 'multi_enum'])->orderBy('id')->cursor() as $value) {
            $mappedOwner = $targets->get($value->attribute_definition_id);
            if ($mappedOwner === null || $mappedOwner === $value->attribute_definition_id) {
                continue;
            }
            $codes = $value->value_type === 'enum' ? [$value->value_enum_code] : json_decode($value->value_json ?? 'null', true);
            $vocabulary = $codesByDefinition->get($value->attribute_definition_id, collect());
            if (! is_array($codes) || array_diff($codes, $vocabulary->keys()->all()) !== []) {
                $issues['unresolved_product_option_codes'][] = $value->id;

                continue;
            }
            $converted = array_map(fn ($code) => $vocabulary->get($code)->canonical_code, $codes);
            if (count(array_unique($converted)) !== count($converted)) {
                $issues['product_option_merge_conflicts'][] = $value->id;
            }
        }
        $assignmentGroups = [];
        foreach ($this->table('category_attribute_assignments')->orderBy('id')->get() as $assignment) {
            $assignmentGroups[$assignment->central_category_id.':'.($targets->get($assignment->attribute_definition_id) ?? $assignment->attribute_definition_id)][] = $assignment->id;
        }
        foreach ($assignmentGroups as $ids) {
            if (count($ids) > 1) {
                $issues['assignment_merge_conflicts'][] = $ids;
            }
        }
        $contentGroups = [];
        foreach ($this->table('content_relations')->where('related_type', 'attribute')->orderBy('id')->get() as $relation) {
            $key = $relation->content_item_id.':'.$relation->relation_type.':'.($targets->get($relation->related_id) ?? $relation->related_id);
            $contentGroups[$key][] = $relation->id;
        }
        foreach ($contentGroups as $ids) {
            if (count($ids) > 1) {
                $issues['content_relation_merge_conflicts'][] = $ids;
            }
        }
        ksort($issues, SORT_STRING);

        return $issues;
    }

    private function table(string $table): Builder
    {
        return (new AttributeDefinition)->getConnection()->table($table);
    }
}
