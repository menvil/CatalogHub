<?php

namespace Tests\Feature\Attributes;

use App\Actions\Translations\SaveAttributeOptionTranslationAction;
use App\Actions\Translations\SaveAttributeSectionTranslationAction;
use App\Actions\Translations\SaveAttributeTranslationAction;
use App\Actions\Translations\SaveCategoryTranslationAction;
use App\Actions\Translations\SaveUnitTranslationAction;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Locale;
use App\Models\MeasurementUnit;
use App\Models\Translations\AttributeTranslation;
use App\Services\AttributeGlobalization\AttributeDependencies;
use App\Services\Translations\TranslationSourceHashService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class AttributeGlobalizationReviewRegressionTest extends TestCase
{
    use RefreshDatabase;

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
            self::assertNotEmpty(array_filter($queries, fn ($query) => str_contains($query['query'], 'attribute_identity_scopes')));
            self::assertNotEmpty(array_filter($queries, fn ($query) => str_contains($query['query'], $owner->getTable())));
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
