<?php

namespace App\Services\AttributeGlobalization;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\Permission;
use App\Enums\SchemaMutationOrigin;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeDefinitionCrosswalk;
use App\Models\CentralCatalog\AttributeIdentityScope;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeOptionCrosswalk;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CategoryComparisonAttribute;
use App\Models\CentralCatalog\SchemaConsumerDecision;
use App\Models\CentralCatalog\SchemaConsumerReconciliationPlan;
use App\Models\CentralCatalog\SchemaConsumerSectionDecision;
use App\Models\User;
use App\Queries\Attributes\CanonicalMergePreflightV2Query;
use App\Queries\Attributes\SchemaConsumerPreflightV2Query;
use App\Services\Audit\AuditRecorder;
use App\Services\Categories\CategoryAccess;
use App\Services\Categories\CategoryLock;
use App\Services\CategorySchema\SchemaRevision;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Explicit reviewed decisions only. Repointing is the frozen cutover migration. */
final class AttributeReconciliationWriter
{
    /** @param array<string, mixed> $plan
     * @return array<string, mixed>
     */
    public function run(array $plan, bool $apply, ?User $actor): array
    {
        $actor = app(CategoryAccess::class)->authorize(Permission::CatalogSchemaManage, $actor);
        Validator::make($plan, [
            'version' => ['required', 'in:1'], 'expected_write_epoch' => ['required', 'integer', 'min:0'],
            'definitions' => ['present', 'array', 'max:1000'], 'definitions.*' => ['array:legacy_definition_id,canonical_definition_id,canonical_code,measurement_dimension_id,canonical_measurement_unit_id'],
            'definitions.*.legacy_definition_id' => ['required', 'integer', 'distinct'], 'definitions.*.canonical_definition_id' => ['required', 'integer'], 'definitions.*.canonical_code' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]*$/', 'max:255'],
            'options' => ['sometimes', 'array', 'max:10000'], 'options.*' => ['array:legacy_option_id,canonical_option_id'], 'options.*.legacy_option_id' => ['required', 'integer', 'distinct'], 'options.*.canonical_option_id' => ['required', 'integer'],
            'sections' => ['sometimes', 'array', 'max:1000'], 'sections.*' => ['array:section_id,expected_parent_id,position'], 'sections.*.section_id' => ['required', 'integer', 'distinct'], 'sections.*.expected_parent_id' => ['present', 'nullable', 'integer'], 'sections.*.position' => ['required', 'integer', 'min:0'],
            'translations' => ['sometimes', 'array', 'max:1000'], 'translations.*' => ['array:owner_table,translation_id,expected_locale_id,locale_id'],
            'translations.*.owner_table' => ['required', Rule::in(array_keys(TranslationLocaleReconciliation::OWNERS))],
            'translations.*.translation_id' => ['required', 'integer'], 'translations.*.expected_locale_id' => ['present', 'nullable', 'integer'], 'translations.*.locale_id' => ['required', 'integer'],
            'facets' => ['sometimes', 'array', 'max:1000'], 'facets.*' => ['array:legacy_definition_id,decision'], 'facets.*.legacy_definition_id' => ['required', 'integer', 'distinct'], 'facets.*.decision' => ['required', 'in:omit'],
            'comparison' => ['sometimes', 'array', 'max:1000'], 'comparison.*' => ['array:legacy_definition_id,assignment_ids'], 'comparison.*.legacy_definition_id' => ['required', 'integer', 'distinct'], 'comparison.*.assignment_ids' => ['required', 'array'], 'comparison.*.assignment_ids.*' => ['integer', 'distinct'],
        ])->validate();
        if (array_diff(array_keys($plan), ['version', 'expected_write_epoch', 'definitions', 'options', 'sections', 'facets', 'comparison', 'translations']) !== []) {
            $this->invalid('Unknown reconciliation instruction.');
        }
        if (array_sum(array_map(fn ($key) => count($plan[$key] ?? []), ['definitions', 'options', 'sections', 'facets', 'comparison', 'translations'])) === 0) {
            $this->invalid('At least one explicit reconciliation decision is required.');
        }
        foreach (collect($plan['definitions'])->groupBy('canonical_definition_id') as $group) {
            if ($group->pluck('canonical_code')->unique()->count() !== 1) {
                $this->invalid('Conflicting canonical code decisions for one target; last-write-wins is forbidden.');
            }
        }
        $hash = hash('sha256', json_encode($plan, JSON_THROW_ON_ERROR));
        DB::beginTransaction();
        try {
            app(AttributeIdentityLock::class)->acquire();
            $scope = AttributeIdentityScope::query()->whereKey(1)->firstOrFail();
            if (SchemaConsumerReconciliationPlan::query()->whereKey($hash)->exists()) {
                DB::rollBack();

                return ['applied' => false, 'already_applied' => true, 'report' => app(SchemaConsumerPreflightV2Query::class)->report()];
            }
            if ((int) $scope->consumer_version !== 1) {
                $this->invalid('Reconciliation decisions must precede the consumer cutover.');
            }
            $optionTargets = AttributeOption::query()->whereIn('id', array_column($plan['options'] ?? [], 'canonical_option_id'))->pluck('attribute_definition_id');
            $optionOwners = AttributeOptionCrosswalk::query()->whereIn('legacy_option_id', array_column($plan['options'] ?? [], 'legacy_option_id'))->pluck('legacy_definition_id');
            $ownerIds = collect([...$plan['definitions'], ...($plan['facets'] ?? []), ...($plan['comparison'] ?? [])])->pluck('legacy_definition_id')->merge($optionOwners)->merge($optionTargets)->unique();
            if ((int) $scope->write_epoch !== (int) $plan['expected_write_epoch']) {
                $this->invalid('Identity inventory changed. Review a fresh diagnostic report before applying.');
            }
            $affected = CategoryAttributeAssignment::query()->whereIn('attribute_definition_id', [...$ownerIds->all(), ...array_column($plan['definitions'], 'canonical_definition_id')])->pluck('central_category_id')->all();
            $affected = [...$affected, ...AttributeSection::query()->whereIn('id', array_column($plan['sections'] ?? [], 'section_id'))->pluck('central_category_id')->all()];
            foreach ($plan['translations'] ?? [] as $decision) {
                $affected = [...$affected, ...app(TranslationLocaleReconciliation::class)->categories($decision)];
            }
            $categories = app(CategoryLock::class)->acquire($affected);
            foreach ($categories as $category) {
                app(SchemaRevision::class)->assertMutable($category);
            }
            $definitions = AttributeDefinition::query()->whereIn('id', [...$ownerIds->all(), ...array_column($plan['definitions'], 'canonical_definition_id')])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($plan['definitions'] as $decision) {
                $legacy = $definitions->get($decision['legacy_definition_id']);
                $target = $definitions->get($decision['canonical_definition_id']);
                $crosswalk = AttributeDefinitionCrosswalk::query()->whereKey($decision['legacy_definition_id'])->lockForUpdate()->first();
                if ($legacy === null || $target === null || $crosswalk === null) {
                    $this->invalid('Every decision must name an existing legacy identity and a retained canonical target.');
                }
                $pair = app(GlobalAttributeValidation::class)->validate(['code' => $decision['canonical_code'], 'name' => $target->name, 'data_type' => $target->data_type,
                    'measurement_dimension_id' => $decision['measurement_dimension_id'] ?? $target->measurement_dimension_id,
                    'canonical_measurement_unit_id' => $decision['canonical_measurement_unit_id'] ?? $target->canonical_measurement_unit_id]);
                if ($legacy->id !== $target->id && ($legacy->data_type !== $target->data_type || $legacy->measurement_dimension_id !== $pair['measurement_dimension_id'] || $legacy->canonical_measurement_unit_id !== $pair['canonical_measurement_unit_id'])) {
                    $this->invalid('Type/measurement conversion requires a separate proven migration; this plan cannot reinterpret Product facts.');
                }
                if ($target->measurement_dimension_id !== null && [$target->measurement_dimension_id, $target->canonical_measurement_unit_id] !== [$pair['measurement_dimension_id'], $pair['canonical_measurement_unit_id']]
                    && app(AttributeDependencies::class)->forDefinition($target) !== []) {
                    $this->invalid('Existing relational measurement meaning has dependencies; a proven conversion migration is required.');
                }
                $target->forceFill([...$pair, 'canonical_code' => $decision['canonical_code']])->saveOrFail();
                $crosswalk->forceFill(['canonical_definition_id' => $target->id, 'canonical_code' => $decision['canonical_code'], 'identity_status' => 'reconciled_explicit',
                    'measurement_status' => 'reconciled_explicit', 'reason' => 'Explicit authorized operator decision.'])->saveOrFail();
                SchemaConsumerDecision::query()->updateOrCreate(['owner_type' => 'definition', 'owner_id' => $legacy->id], ['decision' => 'canonical_target', 'plan_hash' => $hash, 'actor_id' => $actor->id]);
                if ($apply) {
                    app(AuditRecorder::class)->record(AuditAction::CatalogAttributeIdentityReconciled, AuditContext::Central, $actor, $target, null, null,
                        ['definition_id' => $legacy->id, 'target_definition_id' => $target->id, 'code' => $decision['canonical_code'], 'changed_fields' => ['canonical_identity'], 'affected_category_count' => count($categories)]);
                }
            }
            foreach ($plan['options'] ?? [] as $decision) {
                $crosswalk = AttributeOptionCrosswalk::query()->whereKey($decision['legacy_option_id'])->lockForUpdate()->firstOrFail();
                $target = AttributeOption::query()->whereKey($decision['canonical_option_id'])->lockForUpdate()->firstOrFail();
                $definitionTarget = AttributeDefinitionCrosswalk::query()->whereKey($crosswalk->legacy_definition_id)->value('canonical_definition_id');
                $optionOwnerTarget = AttributeDefinitionCrosswalk::query()->whereKey($target->attribute_definition_id)->value('canonical_definition_id') ?? $target->attribute_definition_id;
                if ($definitionTarget === null || $definitionTarget !== $optionOwnerTarget) {
                    $this->invalid('Option map must stay within the explicitly selected canonical meaning.');
                }
                $crosswalk->forceFill(['canonical_option_id' => $target->id, 'canonical_code' => $target->code, 'status' => 'reconciled_explicit'])->saveOrFail();
                if ($apply) {
                    app(AuditRecorder::class)->record(AuditAction::CatalogAttributeIdentityReconciled, AuditContext::Central, $actor, $target, null, null,
                        ['definition_id' => $crosswalk->legacy_definition_id, 'target_definition_id' => $definitionTarget,
                            'option_id' => $crosswalk->legacy_option_id, 'target_option_id' => $target->id, 'changed_fields' => ['option_identity']]);
                }
            }
            foreach ($plan['sections'] ?? [] as $decision) {
                $section = AttributeSection::query()->whereKey($decision['section_id'])->lockForUpdate()->firstOrFail();
                if ($section->parent_id !== $decision['expected_parent_id']) {
                    $this->invalid('Section parent evidence changed.');
                }
                SchemaConsumerSectionDecision::query()->create(['section_id' => $section->id, 'plan_hash' => $hash,
                    'legacy_parent_id' => $section->parent_id, 'legacy_position' => $section->position, 'target_position' => $decision['position']]);
                if ($apply) {
                    app(AuditRecorder::class)->record(AuditAction::CatalogAttributeIdentityReconciled, AuditContext::Central, $actor, $section, null,
                        ['section_id' => $section->id, 'legacy_parent_id' => $section->parent_id, 'position' => $section->position],
                        ['section_id' => $section->id, 'position' => $decision['position'], 'changed_fields' => ['parent_id', 'position']]);
                }
                $section->forceFill(['parent_id' => null, 'position' => $decision['position']])->saveOrFail();
            }
            foreach (['facets' => 'facet', 'comparison' => 'comparison'] as $key => $type) {
                foreach ($plan[$key] ?? [] as $decision) {
                    $definition = AttributeDefinition::query()->findOrFail($decision['legacy_definition_id']);
                    if ($type === 'comparison') {
                        foreach ($decision['assignment_ids'] as $position => $assignmentId) {
                            $assignment = CategoryAttributeAssignment::query()->whereKey($assignmentId)->where('attribute_definition_id', $definition->id)->firstOrFail();
                            CategoryComparisonAttribute::query()->updateOrCreate(['central_category_id' => $assignment->central_category_id, 'category_attribute_assignment_id' => $assignment->id], ['position' => $position, 'is_visible' => true]);
                        }
                    }
                    if ($apply) {
                        app(AuditRecorder::class)->record(AuditAction::CatalogAttributeIdentityReconciled, AuditContext::Central, $actor, $definition, null, null,
                            ['definition_id' => $definition->id, 'changed_fields' => [$type.'_authority_decision'], 'affected_category_count' => count($categories)]);
                    }
                    SchemaConsumerDecision::query()->updateOrCreate(['owner_type' => $type, 'owner_id' => $definition->id], ['decision' => $type === 'facet' ? 'omit' : 'explicit_rows', 'plan_hash' => $hash, 'actor_id' => $actor->id]);
                }
            }
            foreach ($plan['translations'] ?? [] as $decision) {
                app(TranslationLocaleReconciliation::class)->apply($decision, $hash, $actor, $apply);
            }
            $mergeBlockers = app(CanonicalMergePreflightV2Query::class)->blockers();
            if ($mergeBlockers !== []) {
                $this->invalid('Canonical merge requires explicit conflict resolution: '.json_encode($mergeBlockers, JSON_THROW_ON_ERROR));
            }
            $report = app(SchemaConsumerPreflightV2Query::class)->report();
            if (isset($report['blockers']['product_value_merge_conflicts'])) {
                $this->invalid('Product facts would collapse to one canonical definition; explicit fact migration required.');
            }
            if (($plan['translations'] ?? []) !== [] && isset($report['blockers']['translation_locale_collisions'])) {
                $this->invalid('Locale decisions still collapse translated rows; no winner may be selected.');
            }
            if ($apply) {
                foreach ($categories as $category) {
                    app(SchemaRevision::class)->invalidate($category, SchemaMutationOrigin::IdentityReconciled, null, $actor);
                }
                SchemaConsumerReconciliationPlan::query()->create(['plan_hash' => $hash, 'actor_id' => $actor->id,
                    'decision_count' => count($plan['definitions']) + count($plan['options'] ?? []) + count($plan['sections'] ?? []) + count($plan['facets'] ?? []) + count($plan['comparison'] ?? []) + count($plan['translations'] ?? []), 'applied_at' => now()]);
                app(AttributeIdentityLock::class)->recordTargetWrite();
                DB::commit();
            } else {
                DB::rollBack();
            }

            return ['applied' => $apply, 'already_applied' => false, 'report' => $report];
        } catch (Throwable $error) {
            DB::rollBack();
            throw $error;
        }
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['plan' => $message]);
    }
}
