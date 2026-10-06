<?php

namespace App\Queries\Attributes;

use App\Contracts\Persistence\RawSqlPersistenceBoundary;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\Imports\NormalizedProductDraft;
use App\Services\AttributeGlobalization\DraftAttributeIdentityV2;
use App\Services\AttributeGlobalization\SchemaConsumerVersion;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\ValidationException;

/** Read-only v2 gate, also used before any contraction DDL. No equivalence decisions. */
final class SchemaConsumerPreflightV2Query implements RawSqlPersistenceBoundary
{
    /** @return array<string, mixed> */
    public function report(): array
    {
        $issues = app(CanonicalMergePreflightV2Query::class)->blockers();
        $definitions = $this->table('attribute_definitions')->orderBy('id')->get()->keyBy('id');
        $assignments = $this->table('category_attribute_assignments')->orderBy('id')->get()->keyBy('id');
        $membership = $assignments->keyBy(fn ($a) => $a->central_category_id.':'.$a->attribute_definition_id);
        $crosswalks = $this->table('attribute_definition_crosswalks')->orderBy('legacy_definition_id')->get()->keyBy('legacy_definition_id');
        $targets = $crosswalks->pluck('canonical_definition_id', 'legacy_definition_id');
        foreach ($crosswalks as $row) {
            $target = $definitions->get($row->canonical_definition_id);
            $targetCrosswalk = $target === null ? null : $crosswalks->get($target->id);
            if ($targetCrosswalk !== null && $targetCrosswalk->canonical_definition_id !== $target->id) {
                $issues['canonical_target_ambiguities'][] = $row->legacy_definition_id;
            }
            if ($target === null || $row->canonical_code !== $target->code || ! in_array($row->identity_status, ['identity_preserved', 'reconciled_explicit'], true)) {
                $issues['unresolved_canonical_identity_rows'][] = $row->legacy_definition_id;
            }
            if (! in_array($row->measurement_status, ['unmeasured', 'resolved_exact', 'reconciled_explicit'], true)) {
                $issues['measurement_mapping_failures'][] = $row->legacy_definition_id;
            }
        }
        foreach ($definitions->filter(fn ($d) => ($targets->get($d->id) ?? $d->id) === $d->id)->groupBy(fn ($d) => strtolower($d->code)) as $code => $group) {
            if ($group->count() > 1) {
                $issues['duplicate_global_codes'][] = ['code' => $code, 'definition_ids' => $group->pluck('id')->all()];
            }
        }
        foreach ($definitions->filter(fn ($d) => ($targets->get($d->id) ?? $d->id) === $d->id) as $definition) {
            if (! preg_match('/^[a-z][a-z0-9_]*$/', $definition->code)) {
                $issues['invalid_canonical_vocabulary'][] = $definition->id;
            }
        }
        foreach ($definitions->filter(fn ($d) => ($targets->get($d->id) ?? $d->id) === $d->id) as $definition) {
            $historical = $crosswalks->where('legacy_code', $definition->code);
            if ($historical->isNotEmpty() && ! $historical->contains('canonical_definition_id', $definition->id)) {
                $issues['historical_code_reservation_conflicts'][] = $definition->id;
            }
        }
        $units = $this->table('measurement_units')->get()->keyBy('id');
        foreach ($definitions as $definition) {
            if (isset($definition->central_category_id) && ! $crosswalks->has($definition->id)) {
                $issues['unresolved_canonical_identity_rows'][] = $definition->id;
            }
            $dimension = $definition->measurement_dimension_id;
            $unit = $units->get($definition->canonical_measurement_unit_id);
            if (($dimension === null) !== ($definition->canonical_measurement_unit_id === null)
                || ($dimension !== null && ($unit === null || $unit->dimension_id !== $dimension || ! in_array($definition->data_type, ['integer', 'decimal'], true)))) {
                $issues['incompatible_measurements'][] = $definition->id;
            }
            if (isset($definition->central_category_id) && $membership->get($definition->central_category_id.':'.$definition->id) === null) {
                $issues['assignment_membership_gaps'][] = $definition->id;
            }
        }
        $optionCrosswalks = $this->table('attribute_option_crosswalks as x')->leftJoin('attribute_options as o', 'o.id', '=', 'x.canonical_option_id')
            ->orderBy('x.legacy_option_id')->get(['x.*', 'o.code as target_code', 'o.attribute_definition_id as target_definition_id']);
        foreach ($optionCrosswalks->groupBy(fn ($row) => $row->legacy_definition_id.':'.$row->legacy_code) as $identity => $rows) {
            if ($rows->count() > 1) {
                $issues['historical_option_code_conflicts'][] = ['identity' => $identity, 'option_ids' => $rows->pluck('legacy_option_id')->all()];
            }
        }
        foreach ($optionCrosswalks as $row) {
            $canonicalDefinition = $targets->get($row->legacy_definition_id) ?? $row->legacy_definition_id;
            if ($row->canonical_option_id === null || $row->canonical_code !== $row->target_code || $canonicalDefinition !== ($targets->get($row->target_definition_id) ?? $row->target_definition_id)
                || ! in_array($row->status, ['identity_preserved', 'reconciled_explicit'], true)) {
                $issues['invalid_option_crosswalks'][] = $row->legacy_option_id;
            }
        }
        $options = $this->table('attribute_options')->orderBy('id')->get()->groupBy('attribute_definition_id');
        $unitsByCode = $units->keyBy('code');
        $hash = hash_init('sha256');
        $count = 0;
        $currentProduct = null;
        $productGroups = [];
        // The scan retains only one Product's identities, not the complete Product catalog.
        foreach ($this->table('central_product_attribute_values as v')
            ->leftJoin('central_products as p', 'p.id', '=', 'v.central_product_id')
            ->select(['v.*', 'p.central_category_id as product_category_id'])->orderBy('v.central_product_id')->orderBy('v.id')->cursor() as $value) {
            $categoryId = $value->product_category_id;
            unset($value->product_category_id);
            $count++;
            hash_update($hash, json_encode($value, JSON_THROW_ON_ERROR)."\n");
            if ($membership->get(($categoryId ?? '').':'.$value->attribute_definition_id) === null) {
                $issues['product_membership_gaps'][] = $value->id;
            }
            if ($currentProduct !== $value->central_product_id) {
                $collisions = $this->collisions($productGroups);
                if ($collisions !== []) {
                    $issues['product_value_merge_conflicts'] = [...($issues['product_value_merge_conflicts'] ?? []), ...$collisions];
                }
                $productGroups = [];
                $currentProduct = $value->central_product_id;
            }
            $productGroups[$targets->get($value->attribute_definition_id) ?? $value->attribute_definition_id][] = $value->id;
            $definition = $definitions->get($value->attribute_definition_id);
            if ($definition !== null && $definition->data_type !== $value->value_type) {
                $issues['product_value_shape_conflicts'][] = $value->id;
            }
            if (in_array($value->value_type, ['enum', 'multi_enum'], true)) {
                $codes = $value->value_type === 'enum' ? [$value->value_enum_code] : json_decode($value->value_json ?? 'null', true);
                $vocabulary = $options->get($value->attribute_definition_id, collect())->pluck('code')->all();
                if (! is_array($codes) || array_filter($codes, fn ($code) => ! is_string($code) || ! in_array($code, $vocabulary, true)) !== []) {
                    $issues['unresolved_product_option_codes'][] = $value->id;
                }
            }
            if ($definition !== null && $definition->measurement_dimension_id !== null) {
                foreach (['source_unit', 'canonical_unit'] as $field) {
                    if ($value->$field !== null && $unitsByCode->get($value->$field)?->dimension_id !== $definition->measurement_dimension_id) {
                        $issues['product_unit_snapshot_conflicts'][] = ['value_id' => $value->id, 'field' => $field];
                    }
                }
            }
        }
        $collisions = $this->collisions($productGroups);
        if ($collisions !== []) {
            $issues['product_value_merge_conflicts'] = [...($issues['product_value_merge_conflicts'] ?? []), ...$collisions];
        }
        $productGroups = [];
        foreach ($this->table('attribute_mappings')->orderBy('id')->get() as $mapping) {
            $assignment = $assignments->get($mapping->category_attribute_assignment_id);
            if (($assignment === null && ($mapping->category_attribute_assignment_id !== null || isset($mapping->attribute_definition_id)))
                || ($assignment !== null && ($assignment->central_category_id !== $mapping->category_id
                    || (isset($mapping->attribute_definition_id) && $assignment->attribute_definition_id !== $mapping->attribute_definition_id)))) {
                $issues['import_membership_gaps'][] = $mapping->id;
            }
        }
        $facetOptions = $this->table('facet_options')->orderBy('id')->get()->groupBy('facet_definition_id');
        foreach ($this->table('facet_definitions')->orderBy('id')->get() as $facet) {
            $assignment = $assignments->get($facet->category_attribute_assignment_id);
            if (($facet->source_type === 'attribute' && ($assignment === null || $assignment->central_category_id !== $facet->category_id
                    || (isset($facet->attribute_definition_id) && $assignment->attribute_definition_id !== $facet->attribute_definition_id)))
                || ($facet->source_type !== 'attribute' && $facet->category_attribute_assignment_id !== null)) {
                $issues['facet_membership_conflicts'][] = $facet->id;
            }
            $definition = $assignment === null ? null : $definitions->get($assignment->attribute_definition_id);
            $validType = match ($facet->source_type) {
                'brand' => in_array($facet->facet_type, ['checkbox', 'select'], true),
                'rating' => $facet->facet_type === 'range',
                'attribute' => $definition !== null && match ($facet->facet_type) {
                    'checkbox', 'select' => in_array($definition->data_type, ['enum', 'multi_enum'], true),
                    'range' => in_array($definition->data_type, ['integer', 'decimal'], true),
                    'boolean' => $definition->data_type === 'boolean',
                    default => false,
                },
                default => false,
            };
            if (! $validType) {
                $issues['facet_type_conflicts'][] = $facet->id;
            }
            if ($definition !== null && in_array($definition->data_type, ['enum', 'multi_enum'], true)) {
                $vocabulary = $options->get($definition->id, collect())->pluck('code')->all();
                foreach ($facetOptions->get($facet->id, collect()) as $option) {
                    if (! in_array($option->value, $vocabulary, true)) {
                        $issues['facet_option_identity_conflicts'][] = $option->id;
                    }
                }
            }
        }
        foreach ($this->table('attribute_display_rules')->orderBy('id')->get() as $rule) {
            $definition = $definitions->get($rule->attribute_definition_id);
            if ($definition === null || $definition->measurement_dimension_id === null
                || $units->get($rule->display_unit_id)?->dimension_id !== $definition->measurement_dimension_id) {
                $issues['display_rule_conflicts'][] = $rule->id;
            }
        }
        foreach ($this->table('category_comparison_attributes')->orderBy('id')->get() as $row) {
            $assignment = $assignments->get($row->category_attribute_assignment_id);
            if ($assignment === null || $assignment->central_category_id !== $row->central_category_id) {
                $issues['comparison_membership_conflicts'][] = $row->id;
            }
        }
        $decisions = $this->table('schema_consumer_decisions')->get()->keyBy(fn ($row) => $row->owner_type.':'.$row->owner_id);
        $facetAssignments = $this->table('facet_definitions')->where('source_type', 'attribute')->pluck('category_attribute_assignment_id')->flip();
        $comparisonAssignments = $this->table('category_comparison_attributes')->pluck('category_attribute_assignment_id')->flip();
        $assignmentsByDefinition = $assignments->groupBy('attribute_definition_id');
        foreach ($definitions as $definition) {
            $localIds = $assignmentsByDefinition->get($definition->id, collect())->pluck('id');
            if (($definition->is_filterable ?? false) && $localIds->intersect($facetAssignments->keys())->isEmpty()
                && $decisions->get('facet:'.$definition->id) === null) {
                $issues['unreconciled_filterable_flags'][] = $definition->id;
            }
            if (($definition->is_comparable ?? false) && $localIds->intersect($comparisonAssignments->keys())->isEmpty()
                && $decisions->get('comparison:'.$definition->id) === null) {
                $issues['unreconciled_comparable_flags'][] = $definition->id;
            }
        }
        foreach ($this->table('attribute_sections')->whereNotNull('parent_id')->orderBy('id')->get(['id', 'parent_id', 'central_category_id', 'position']) as $section) {
            $issues['nested_sections'][] = (array) $section;
        }
        foreach (['category_translations' => 'category_id', 'attribute_translations' => 'attribute_definition_id', 'attribute_section_translations' => 'attribute_section_id', 'attribute_option_translations' => 'attribute_option_id', 'unit_translations' => 'measurement_unit_id', 'product_translations' => 'product_id'] as $table => $owner) {
            foreach ($this->table($table)->whereNull('locale_id')->orderBy('id')->pluck('id') as $id) {
                $issues['unresolved_translation_locale_ids'][] = ['table' => $table, 'row_id' => $id];
            }
            foreach ($this->table($table)->select([$owner, 'locale_id'])->groupBy($owner, 'locale_id')->havingRaw('COUNT(*) > 1')->orderBy($owner)->orderBy('locale_id')->get() as $collision) {
                $issues['translation_locale_collisions'][] = ['table' => $table, ...(array) $collision];
            }
        }
        foreach ($this->table('content_relations')->where('related_type', 'attribute')->orderBy('id')->get() as $relation) {
            if (! $definitions->has($targets->get($relation->related_id) ?? $relation->related_id)) {
                $issues['content_target_conflicts'][] = $relation->id;
            }
        }
        foreach (NormalizedProductDraft::query()->whereIn('status', SchemaConsumerVersion::PUBLISHABLE_STATUSES)->orderBy('id')->cursor() as $draft) {
            try {
                app(DraftAttributeIdentityV2::class)->candidates($draft);
            } catch (ValidationException $error) {
                $issues['unresolved_draft_identity'][] = ['draft_id' => $draft->id, 'reason' => $error->errors()['attributes_json'][0] ?? 'unresolved_identity'];
            }
        }
        ksort($issues, SORT_STRING);

        return ['consumer_version' => 2, 'write_epoch' => (int) $this->table('attribute_identity_scopes')->where('id', 1)->value('write_epoch'), 'legacy_definition_count' => $crosswalks->count(), 'cutover_ready' => $issues === [], 'definition_count' => $definitions->count(),
            'assignment_count' => $assignments->count(), 'product_value_count' => $count, 'product_value_checksum' => hash_final($hash), 'blockers' => $issues];
    }

    public function assertReady(): void
    {
        $report = $this->report();
        if (! $report['cutover_ready']) {
            throw new \RuntimeException('Schema consumer cutover refused before contraction: '.json_encode($report, JSON_THROW_ON_ERROR));
        }
    }

    private function table(string $table): Builder
    {
        return (new AttributeDefinition)->getConnection()->table($table);
    }

    /** @param array<int, list<int>> $groups
     * @return list<list<int>>
     */
    private function collisions(array $groups): array
    {
        return array_values(array_filter($groups, static fn (array $ids): bool => count($ids) > 1));
    }
}
