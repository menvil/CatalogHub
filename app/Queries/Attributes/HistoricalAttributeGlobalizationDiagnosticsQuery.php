<?php

namespace App\Queries\Attributes;

use App\Enums\Permission;
use App\Models\AttributeDisplayRule;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeDefinitionCrosswalk;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeOptionCrosswalk;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\ContentRelation;
use App\Models\FacetDefinition;
use App\Models\Imports\AttributeMapping;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Translations\AttributeOptionTranslation;
use App\Models\Translations\AttributeSectionTranslation;
use App\Models\Translations\AttributeTranslation;
use App\Models\Translations\CategoryTranslation;
use App\Models\Translations\ProductTranslation;
use App\Models\Translations\UnitTranslation;
use App\Models\User;
use App\Services\AttributeGlobalization\LegacyAttributeBackfill;
use App\Services\Categories\CategoryAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

/** Historical identity-v1 inventory only; never used by current consumers. */
final class HistoricalAttributeGlobalizationDiagnosticsQuery
{
    /** @return array<string, mixed>
     * @phpstan-impure
     */
    public function report(?User $actor): array
    {
        if (! app(CategoryAccess::class)->allows(Permission::CatalogSchemaManage, false, $actor)) {
            throw new AuthorizationException;
        }
        $definitions = AttributeDefinition::query()->orderBy('id')->get();
        $assignments = CategoryAttributeAssignment::query()->orderBy('id')->get();
        $crosswalks = AttributeDefinitionCrosswalk::query()->orderBy('legacy_definition_id')->get()->keyBy('legacy_definition_id');
        $assignmentsById = $assignments->keyBy('id');
        $missingAssignments = [];
        $assignmentDrift = [];
        $unresolved = $crosswalks->whereNull('canonical_definition_id')->keys()->all();
        $measurements = [];
        $groups = [];
        $nameGroups = [];
        $definitionById = [];
        $membership = [];
        foreach ($assignments as $assignment) {
            $membership[$assignment->central_category_id.':'.$assignment->attribute_definition_id] = $assignment->id;
        }
        foreach ($definitions as $definition) {
            $definitionById[$definition->id] = $definition;
            $groups[$definition->code][] = $definition;
            $nameGroups[$definition->name][] = $definition->id;
            if ($definition->central_category_id !== null && ! isset($membership[$definition->central_category_id.':'.$definition->id])) {
                $missingAssignments[] = $definition->id;
            }
            $localAssignment = $assignmentsById->get($membership[$definition->central_category_id.':'.$definition->id] ?? null);
            if ($localAssignment !== null) {
                foreach (LegacyAttributeBackfill::LOCAL_FIELDS as $field) {
                    if ($localAssignment->getAttribute($field) !== $definition->getAttribute($field)) {
                        $assignmentDrift[] = ['definition_id' => $definition->id, 'assignment_id' => $localAssignment->id, 'field' => $field];
                    }
                }
            }
            $crosswalk = $crosswalks->get($definition->id);
            if ($definition->canonical_code === null || ($definition->central_category_id !== null && $crosswalk === null)) {
                $unresolved[] = $definition->id;
            }
            $mapping = app(LegacyAttributeBackfill::class)->measurement($definition->dimension, $definition->canonical_unit, $definition->data_type->value);
            if (! in_array($mapping['status'], ['unmeasured', 'resolved_exact'], true)
                || $mapping['dimension_id'] !== $definition->measurement_dimension_id || $mapping['unit_id'] !== $definition->canonical_measurement_unit_id) {
                $measurements[] = ['definition_id' => $definition->id, 'status' => $mapping['status']];
            }
        }
        $unresolved = array_values(array_unique($unresolved));
        sort($unresolved, SORT_NUMERIC);
        $optionIdentityProblems = AttributeOptionCrosswalk::query()->toBase()
            ->leftJoin('attribute_options as canonical', 'canonical.id', '=', 'attribute_option_crosswalks.canonical_option_id')
            ->where(fn ($query) => $query->whereNull('canonical.id')->orWhere('status', '!=', 'identity_preserved')->orWhereColumn('canonical_code', '!=', 'canonical.code')->orWhereNull('canonical_code'))
            ->orderBy('legacy_option_id')->get(['legacy_option_id', 'legacy_definition_id', 'canonical_option_id', 'status'])->map(fn ($row) => (array) $row)->all();
        ksort($groups, SORT_STRING);
        $duplicates = [];
        $possible = [];
        $incompatible = [];
        $optionConflicts = [];
        $displayConflicts = [];
        $duplicateDefinitionIds = [];
        foreach ($groups as $group) {
            if (count($group) > 1) {
                foreach ($group as $definition) {
                    $duplicateDefinitionIds[] = $definition->id;
                }
            }
        }
        $options = AttributeOption::query()->whereIn('attribute_definition_id', $duplicateDefinitionIds)->orderBy('code')->get(['id', 'attribute_definition_id', 'code', 'label', 'position', 'is_visible']);
        $optionsByDefinition = $options->groupBy('attribute_definition_id');
        $rulesByDefinition = AttributeDisplayRule::query()->whereIn('attribute_definition_id', $duplicateDefinitionIds)->orderBy('id')->get()->groupBy('attribute_definition_id');
        $translationsByDefinition = AttributeTranslation::query()->whereIn('attribute_definition_id', $duplicateDefinitionIds)->orderBy('locale_id')->orderBy('id')->get()->groupBy('attribute_definition_id');
        $translationsByOption = AttributeOptionTranslation::query()->whereIn('attribute_option_id', $options->pluck('id'))->orderBy('locale_id')->orderBy('id')->get()->groupBy('attribute_option_id');
        $valuesByDefinition = CentralProductAttributeValue::query()->whereIn('attribute_definition_id', $duplicateDefinitionIds)->select(['attribute_definition_id', 'value_type'])->distinct()->orderBy('value_type')->get()->groupBy('attribute_definition_id');
        $mappingsByDefinition = AttributeMapping::query()->whereIn('attribute_definition_id', $duplicateDefinitionIds)->orderBy('raw_key')->get(['attribute_definition_id', 'raw_key', 'mapping_type', 'status'])->groupBy('attribute_definition_id');
        foreach ($groups as $code => $group) {
            if (count($group) < 2) {
                continue;
            }
            $ids = array_map(fn ($d) => $d->id, $group);
            $fingerprints = [];
            $optionHashes = [];
            $displayHashes = [];
            foreach ($group as $definition) {
                $options = $optionsByDefinition->get($definition->id, collect());
                $optionHash = $this->hash($options->map(fn ($o) => $o->only(['code', 'label', 'position', 'is_visible']))->all());
                $displayHash = $this->hash($rulesByDefinition->get($definition->id, collect())->map(fn ($r) => $r->except(['id', 'attribute_definition_id', 'created_at', 'updated_at']))->all());
                $optionHashes[] = $optionHash;
                $displayHashes[] = $displayHash;
                $translations = $translationsByDefinition->get($definition->id, collect())->map(fn ($r) => $r->except(['id', 'attribute_definition_id', 'created_at', 'updated_at']))->all();
                $optionTranslations = [];
                foreach ($options as $option) {
                    $optionTranslations[$option->code] = $translationsByOption->get($option->id, collect())->map(fn ($r) => $r->except(['id', 'attribute_option_id', 'created_at', 'updated_at']))->all();
                }
                $valueShapes = $valuesByDefinition->get($definition->id, collect())->pluck('value_type')->unique()->values()->all();
                $mappingShapes = $mappingsByDefinition->get($definition->id, collect())->map(fn ($r) => $r->only(['raw_key', 'mapping_type', 'status']))->all();
                $fingerprints[] = $this->hash([$definition->only(['name', 'data_type', 'dimension', 'canonical_unit', 'measurement_dimension_id', 'canonical_measurement_unit_id']), $optionHash, $displayHash, $translations, $optionTranslations, $valueShapes, $mappingShapes]);
            }
            $record = ['code' => $code, 'definition_ids' => $ids, 'status' => 'explicit_reconciliation_required'];
            $duplicates[] = $record;
            if (count(array_unique($fingerprints)) === 1) {
                $possible[] = $record;
            } else {
                $incompatible[] = $record;
            }
            if (count(array_unique($optionHashes)) > 1) {
                $optionConflicts[] = $record;
            }
            if (count(array_unique($displayHashes)) > 1) {
                $displayConflicts[] = $record;
            }
        }
        $differentCodeCandidates = [];
        ksort($nameGroups, SORT_STRING);
        foreach ($nameGroups as $ids) {
            if (count($ids) > 1 && count(array_unique(array_map(fn ($id) => $definitionById[$id]->code, $ids))) > 1) {
                $differentCodeCandidates[] = ['definition_ids' => $ids, 'status' => 'name_only_candidate_not_equivalence'];
            }
        }
        $productMembership = [];
        $mergeGroups = [];
        $productHash = hash_init('sha256');
        $productCount = 0;
        $canonicalIds = $crosswalks->pluck('canonical_definition_id', 'legacy_definition_id')->all();
        foreach (CentralProductAttributeValue::query()->with('product:id,central_category_id')->orderBy('id')->lazyById(500) as $value) {
            $productCount++;
            hash_update($productHash, json_encode($value->getRawOriginal(), JSON_THROW_ON_ERROR)."\n");
            if ($value->product === null || ! isset($membership[$value->product->central_category_id.':'.$value->attribute_definition_id])) {
                $productMembership[] = ['value_id' => $value->id, 'product_id' => $value->central_product_id, 'definition_id' => $value->attribute_definition_id];
            }
            // Actual selected crosswalk target only; same code is never a proposed merge decision.
            $canonicalId = $canonicalIds[$value->attribute_definition_id] ?? $value->attribute_definition_id;
            $mergeGroups[$value->central_product_id.':'.$canonicalId][] = $value->id;
        }
        $mergeConflicts = array_values(array_filter($mergeGroups, fn ($ids) => count($ids) > 1));
        $mappingProblems = [];
        foreach (AttributeMapping::query()->orderBy('id')->cursor() as $mapping) {
            $expected = $mapping->attribute_definition_id === null ? null : ($membership[$mapping->category_id.':'.$mapping->attribute_definition_id] ?? null);
            if ($expected !== $mapping->category_attribute_assignment_id || ($mapping->attribute_definition_id !== null && $expected === null)) {
                $mappingProblems[] = ['mapping_id' => $mapping->id, 'expected_assignment_id' => $expected];
            }
        }
        $drafts = [];
        $draftProblems = [];
        foreach (NormalizedProductDraft::query()->orderBy('id')->cursor() as $draft) {
            $refs = [];
            $payload = json_decode((string) $draft->getRawOriginal('attributes_json'), true);
            if (! is_array($payload)) {
                $draftProblems[] = ['draft_id' => $draft->id, 'reason' => 'unreadable_payload'];

                continue;
            }
            foreach ($payload as $candidate) {
                if (! is_array($candidate)) {
                    $draftProblems[] = ['draft_id' => $draft->id, 'reason' => 'unknown_candidate_shape'];

                    continue;
                }
                $id = isset($candidate['attribute_definition_id']) ? (int) $candidate['attribute_definition_id'] : null;
                $code = $candidate['code'] ?? null;
                $refs[] = ['definition_id' => $id, 'code' => $code, 'option_id' => $candidate['metadata']['option_id'] ?? null, 'source_unit' => $candidate['source_unit'] ?? $candidate['metadata']['source_unit'] ?? null, 'canonical_unit' => $candidate['canonical_unit'] ?? $candidate['metadata']['canonical_unit'] ?? null];
                $exists = $id !== null ? isset($membership[$draft->category_id.':'.$id]) : $definitions->contains(fn ($d) => $d->central_category_id === $draft->category_id && $d->code === $code);
                if (! $exists || $draft->attribute_identity_version !== 1) {
                    $draftProblems[] = ['draft_id' => $draft->id, 'reason' => 'unresolved_definition_or_version'];
                }
            }
            if ($refs !== []) {
                $drafts[] = ['draft_id' => $draft->id, 'category_id' => $draft->category_id, 'identity_version' => $draft->attribute_identity_version, 'reference_count' => count($refs), 'reference_checksum' => $this->hash($refs)];
            }
        }
        $translations = $this->translationCollisions();
        $facetProblems = [];
        foreach (FacetDefinition::query()->whereNotNull('attribute_definition_id')->orderBy('id')->cursor() as $facet) {
            if (! isset($membership[$facet->category_id.':'.$facet->attribute_definition_id])) {
                $facetProblems[] = $facet->id;
            }
        }
        $contentProblems = [];
        foreach (ContentRelation::query()->where('related_type', 'attribute')->orderBy('id')->cursor() as $relation) {
            if (! isset($definitionById[$relation->related_id])) {
                $contentProblems[] = $relation->id;
            }
        }
        $blockers = count($optionIdentityProblems) + count($assignmentDrift) + count($missingAssignments) + count($duplicates) + count($unresolved) + count($measurements) + count($productMembership) + count($mergeConflicts) + count($mappingProblems) + count($draftProblems) + count($translations) + count($facetProblems) + count($contentProblems);

        return ['report_version' => 1, 'identity_version' => 1, 'cutover_ready' => $blockers === 0, 'blocker_count' => $blockers,
            'total_legacy_definitions' => $crosswalks->pluck('legacy_definition_id')->merge($definitions->whereNotNull('central_category_id')->pluck('id'))->unique()->count(),
            'assignment_backfill' => ['total_assignments' => $assignments->count(), 'missing_definition_ids' => $missingAssignments, 'legacy_field_mismatches' => $assignmentDrift, 'checksum' => $this->hash($assignments->map(fn ($r) => $r->only(['central_category_id', 'attribute_definition_id', ...LegacyAttributeBackfill::LOCAL_FIELDS]))->all())],
            'globally_duplicate_codes' => $duplicates, 'possible_equivalence_groups' => $possible, 'incompatible_same_code_groups' => $incompatible, 'different_code_candidates' => $differentCodeCandidates,
            'product_facts' => ['count' => $productCount, 'checksum' => hash_final($productHash)], 'product_membership_problems' => $productMembership, 'product_value_merge_conflicts' => $mergeConflicts,
            'option_code_conflicts' => $optionConflicts, 'option_identity_problems' => $optionIdentityProblems, 'measurement_mapping_failures' => $measurements, 'translation_locale_collisions' => $translations, 'mapping_membership_problems' => $mappingProblems,
            'draft_non_fk_references' => $drafts, 'draft_reference_problems' => $draftProblems, 'display_rule_conflicts' => $displayConflicts,
            'facet_dependencies' => FacetDefinition::query()->whereNotNull('attribute_definition_id')->orderBy('id')->get(['id', 'category_id', 'attribute_definition_id'])->toArray(), 'facet_membership_problems' => $facetProblems,
            'content_dependencies' => ContentRelation::query()->where('related_type', 'attribute')->orderBy('id')->get(['id', 'related_id'])->toArray(), 'content_reference_problems' => $contentProblems,
            'unresolved_canonical_identity_rows' => $unresolved];
    }

    /** @return list<array<string, mixed>> */
    private function translationCollisions(): array
    {
        /** @var array<class-string<Model>, string> $owners */
        $owners = [CategoryTranslation::class => 'category_id', AttributeTranslation::class => 'attribute_definition_id', AttributeSectionTranslation::class => 'attribute_section_id', AttributeOptionTranslation::class => 'attribute_option_id', UnitTranslation::class => 'measurement_unit_id', ProductTranslation::class => 'product_id'];
        $collisions = [];
        foreach ($owners as $class => $owner) {
            $groups = [];
            foreach ($class::query()->orderBy($owner)->orderBy('locale_id')->orderBy('id')->get(['id', $owner, 'locale_id']) as $row) {
                $groups[$row->getAttribute($owner).':'.$row->getAttribute('locale_id')][] = $row->getKey();
            }
            foreach ($groups as $key => $ids) {
                if (count($ids) > 1) {
                    $collisions[] = ['owner_table' => (new $class)->getTable(), 'owner_locale_identity' => $key, 'translation_ids' => $ids];
                }
            }
        }

        return $collisions;
    }

    private function hash(mixed $value): string
    {
        return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
    }
}
