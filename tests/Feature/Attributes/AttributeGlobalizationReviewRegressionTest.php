<?php

namespace Tests\Feature\Attributes;

use App\Actions\CategorySchema\AssignAttributeToCategoryAction;
use App\Actions\CategorySchema\UpdateAttributeDefinitionAction;
use App\Actions\CategorySchema\UpdateGlobalAttributeDefinitionAction;
use App\Actions\Translations\SaveAttributeOptionTranslationAction;
use App\Actions\Translations\SaveAttributeSectionTranslationAction;
use App\Actions\Translations\SaveAttributeTranslationAction;
use App\Actions\Translations\SaveCategoryTranslationAction;
use App\Actions\Translations\SaveUnitTranslationAction;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Locale;
use App\Models\MeasurementUnit;
use App\Models\Translations\AttributeOptionTranslation;
use App\Models\Translations\AttributeTranslation;
use App\Models\User;
use App\Queries\Attributes\AttributeGlobalizationDiagnosticsQuery;
use App\Services\AttributeGlobalization\AttributeDependencies;
use App\Services\AttributeGlobalization\LegacyAttributeBackfill;
use App\Services\Translations\TranslationSourceHashService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class AttributeGlobalizationReviewRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_rename_rejects_a_durable_code_reservation_after_the_live_owner_renamed(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $reserved = AttributeDefinition::factory()->create(['code' => 'reserved_code']);
        $candidate = AttributeDefinition::factory()->create(['code' => 'candidate_code']);
        app(LegacyAttributeBackfill::class)->run();
        app(UpdateGlobalAttributeDefinitionAction::class)->handle($reserved, ['code' => 'new_reserved_code'], $actor);
        $auditCount = AuditLogEntry::query()->count();
        try {
            app(UpdateAttributeDefinitionAction::class)->handle($candidate, ['code' => 'reserved_code', 'name' => $candidate->name, 'data_type' => 'string'], $actor);
            self::fail('A durable legacy code reservation was reused.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('code', $exception->errors());
            self::assertSame('candidate_code', $candidate->fresh()->code);
            self::assertSame(1, $candidate->category->fresh()->schema_revision);
            self::assertSame($auditCount, AuditLogEntry::query()->count());
        }
    }

    public function test_assign_checks_locked_canonical_identity_before_existing_membership(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $definition = AttributeDefinition::factory()->create(['code' => 'unresolved']);
        AttributeDefinition::factory()->create(['code' => 'unresolved']);
        app(LegacyAttributeBackfill::class)->run();
        $definition->canonical_code = 'stale_approved_identity';
        try {
            app(AssignAttributeToCategoryAction::class)->handle($definition->category, $definition, [], 1, $actor);
            self::fail('Unresolved canonical identity was assigned.');
        } catch (ValidationException $exception) {
            self::assertStringContainsString('Resolve canonical identity', $exception->errors()['attribute_definition_id'][0]);
            self::assertSame(2, CategoryAttributeAssignment::query()->count());
            self::assertSame(1, $definition->category->fresh()->schema_revision);
            self::assertSame(0, AuditLogEntry::query()->count());
        }
    }

    public function test_missing_product_relation_is_reported_as_a_blocker_without_changing_the_fact(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        $fact = CentralProductAttributeValue::factory()->forAssignment($assignment)->create();
        $before = $fact->fresh()->getRawOriginal();
        CentralProduct::addGlobalScope('missing_product_fixture', fn ($query) => $query->whereRaw('1 = 0'));
        try {
            $report = app(AttributeGlobalizationDiagnosticsQuery::class)->report($actor);
            self::assertFalse($report['cutover_ready']);
            self::assertSame([['value_id' => $fact->id, 'product_id' => $fact->central_product_id, 'definition_id' => $fact->attribute_definition_id]], $report['product_membership_problems']);
            self::assertSame($before, $fact->fresh()->getRawOriginal());
        } finally {
            CentralProduct::clearBootedModels();
        }
    }

    public function test_duplicate_evidence_queries_stay_bounded_as_definitions_and_options_grow(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $locale = Locale::factory()->create();
        $create = function () use ($locale): void {
            $definition = AttributeDefinition::factory()->create(['code' => 'shared', 'name' => 'Shared', 'data_type' => 'enum']);
            AttributeTranslation::factory()->create(['attribute_definition_id' => $definition->id, 'locale_id' => $locale->id, 'locale' => $locale->code, 'label' => 'Shared', 'short_label' => null, 'help_text' => null, 'status' => 'human_reviewed', 'source_hash' => null, 'approved_by_user_id' => null, 'approved_at' => null]);
            foreach (['one', 'two', 'three'] as $code) {
                $option = AttributeOption::factory()->create(['attribute_definition_id' => $definition->id, 'code' => $code, 'label' => $code, 'position' => 0, 'is_visible' => true]);
                AttributeOptionTranslation::factory()->create(['attribute_option_id' => $option->id, 'locale_id' => $locale->id, 'locale' => $locale->code, 'label' => $code, 'description' => null, 'status' => 'human_reviewed', 'source_hash' => null, 'approved_by_user_id' => null, 'approved_at' => null]);
            }
        };
        $create();
        $create();
        DB::enableQueryLog();
        try {
            DB::flushQueryLog();
            $small = app(AttributeGlobalizationDiagnosticsQuery::class)->report($actor);
            $smallQueries = count(DB::getQueryLog());
            for ($index = 0; $index < 8; $index++) {
                $create();
            }
            DB::flushQueryLog();
            $large = app(AttributeGlobalizationDiagnosticsQuery::class)->report($actor);
            self::assertSame($smallQueries, count(DB::getQueryLog()));
            self::assertCount(1, $small['possible_equivalence_groups']);
            self::assertCount(1, $large['possible_equivalence_groups']);
            self::assertCount(10, $large['possible_equivalence_groups'][0]['definition_ids']);
            self::assertSame([], $large['option_code_conflicts']);
            self::assertSame([], $large['incompatible_same_code_groups']);
        } finally {
            DB::disableQueryLog();
        }
    }

    public function test_draft_candidates_do_not_increase_assignment_queries(): void
    {
        $definition = AttributeDefinition::factory()->global()->create(['code' => 'global_code']);
        $draft = NormalizedProductDraft::factory()->create(['category_id' => CentralCategory::factory(), 'attributes_json' => [['code' => 'global_code']], 'status' => 'pending_review']);
        DB::enableQueryLog();
        try {
            foreach ([1, 100] as $candidateCount) {
                $draft->update(['attributes_json' => array_fill(0, $candidateCount, ['code' => 'global_code'])]);
                DB::flushQueryLog();
                self::assertSame([], app(AttributeDependencies::class)->forDefinition($definition));
                $assignmentQueries = array_filter(DB::getQueryLog(), fn ($query) => str_contains($query['query'], 'category_attribute_assignments'));
                self::assertCount(1, $assignmentQueries);
            }
        } finally {
            DB::disableQueryLog();
        }
    }

    #[DataProvider('draftStatuses')]
    public function test_only_publishable_drafts_block_semantic_mutation(string $status, bool $blocks): void
    {
        $definition = AttributeDefinition::factory()->global()->create();
        $assignment = CategoryAttributeAssignment::factory()->for($definition, 'definition')->create();
        $draft = NormalizedProductDraft::factory()->create(['category_id' => $assignment->central_category_id, 'status' => $status, 'attributes_json' => [['code' => $definition->code]]]);
        self::assertIsInt($draft->fresh()->category_id);
        self::assertIsInt($definition->assignments()->pluck('central_category_id')->sole());
        self::assertSame($blocks, in_array('normalized_drafts', app(AttributeDependencies::class)->forDefinition($definition), true));
    }

    public static function draftStatuses(): array
    {
        return [['pending_review', true], ['approved', true], ['published', false], ['rejected', false], ['failed', false]];
    }

    #[DataProvider('translationOwners')]
    public function test_translation_save_locks_and_reloads_owner_and_locale_before_check_and_creation(string $type): void
    {
        $owner = match ($type) {
            'category' => CentralCategory::factory()->create(),
            'attribute' => AttributeDefinition::factory()->global()->create(),
            'section' => AttributeSection::factory()->create(),
            'option' => AttributeOption::factory()->create(),
            'unit' => MeasurementUnit::factory()->create(),
            default => throw new \LogicException('Unexpected translation owner.'),
        };
        $locale = Locale::factory()->create(['code' => 'before']);
        Locale::query()->whereKey($locale->id)->update(['code' => 'after']);
        $owner->newQuery()->whereKey($owner->id)->update([$owner instanceof AttributeOption ? 'label' : 'name' => 'Current source']);
        $hashes = app(TranslationSourceHashService::class);
        $expectedHash = match ($type) {
            'category' => $hashes->forCategory(CentralCategory::query()->findOrFail($owner->id)),
            'attribute' => $hashes->forAttribute(AttributeDefinition::query()->findOrFail($owner->id)),
            'section' => $hashes->forAttributeSection(AttributeSection::query()->findOrFail($owner->id)),
            'option' => $hashes->forAttributeOption(AttributeOption::query()->findOrFail($owner->id)),
            default => $hashes->forUnit(MeasurementUnit::query()->findOrFail($owner->id)),
        };
        $before = $owner->fresh()->getRawOriginal();
        DB::enableQueryLog();
        try {
            DB::flushQueryLog();
            $translation = $this->saveTranslation($owner, $locale);
            $queries = DB::getQueryLog();
            self::assertStringStartsWith('update', strtolower($queries[0]['query']));
            self::assertStringContainsString($owner->getTable(), $queries[0]['query']);
            self::assertSame('after', $translation->getAttribute('locale'));
            self::assertSame($expectedHash, $translation->getAttribute('source_hash'));
            self::assertSame($before, $owner->fresh()->getRawOriginal());
            self::assertSame($translation->getKey(), $this->saveTranslation($owner, $locale)->getKey());
        } finally {
            DB::disableQueryLog();
        }
    }

    public static function translationOwners(): array
    {
        return [['category'], ['attribute'], ['section'], ['option'], ['unit']];
    }

    public function test_translation_hash_failure_rolls_back_creation(): void
    {
        $owner = AttributeDefinition::factory()->global()->create();
        $locale = Locale::factory()->create();
        AttributeTranslation::saving(function (AttributeTranslation $translation): void {
            if ($translation->source_hash !== null) {
                throw new \RuntimeException('Hash save failed.');
            }
        });
        try {
            app(SaveAttributeTranslationAction::class)->handle($owner, $locale, ['label' => 'Translated']);
            self::fail('Hash save failure was ignored.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Hash save failed.', $exception->getMessage());
            self::assertSame(0, AttributeTranslation::query()->count());
        }
    }

    private function saveTranslation(Model $owner, Locale $locale): Model
    {
        return match (true) {
            $owner instanceof CentralCategory => app(SaveCategoryTranslationAction::class)->handle($owner, $locale, ['name' => 'Translated']),
            $owner instanceof AttributeDefinition => app(SaveAttributeTranslationAction::class)->handle($owner, $locale, ['label' => 'Translated']),
            $owner instanceof AttributeSection => app(SaveAttributeSectionTranslationAction::class)->handle($owner, $locale, ['name' => 'Translated']),
            $owner instanceof AttributeOption => app(SaveAttributeOptionTranslationAction::class)->handle($owner, $locale, ['label' => 'Translated']),
            $owner instanceof MeasurementUnit => app(SaveUnitTranslationAction::class)->handle($owner, $locale, ['short_name' => 'Translated']),
            default => throw new \LogicException('Unexpected translation owner.'),
        };
    }
}
