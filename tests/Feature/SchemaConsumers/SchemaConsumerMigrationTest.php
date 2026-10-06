<?php

namespace Tests\Feature\SchemaConsumers;

use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\SchemaConsumerDecision;
use App\Models\ContentRelation;
use App\Models\Imports\ImportSource;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Locale;
use App\Models\Translations\AttributeSectionTranslation;
use App\Models\Translations\AttributeTranslation;
use App\Models\Translations\CategoryTranslation;
use App\Models\Translations\ProductTranslation;
use App\Models\User;
use App\Queries\Attributes\AttributeGlobalizationDiagnosticsQuery;
use App\Queries\Attributes\SchemaConsumerPreflightV2Query;
use App\Services\AttributeGlobalization\AttributeReconciliationWriter;
use App\Services\AttributeGlobalization\LegacyAttributeBackfill;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

final class SchemaConsumerMigrationTest extends TestCase
{
    use DatabaseTruncation;

    protected function beforeTruncatingDatabase(): void
    {
        RefreshDatabaseState::$migrated = false;
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_06_000002_cut_over_schema_consumers.php');
    }

    public function test_clean_inverse_before_target_writes_and_repeat_cutover(): void
    {
        $this->migration()->down();
        self::assertTrue(Schema::hasColumn('attribute_definitions', 'central_category_id'));
        self::assertSame(1, (int) DB::table('attribute_identity_scopes')->value('consumer_version'));
        $this->migration()->up();
        self::assertFalse(Schema::hasColumn('attribute_definitions', 'central_category_id'));
        self::assertTrue(app(SchemaConsumerPreflightV2Query::class)->report()['cutover_ready']);
    }

    #[DataProvider('postCutoverOwners')]
    public function test_product_translation_and_schema_review_writes_close_inverse_boundary(string $owner): void
    {
        $this->migration()->down();
        $category = CentralCategory::factory()->create();
        $product = CentralProduct::factory()->create(['central_category_id' => $category->id]);
        $translation = ProductTranslation::factory()->create(['product_id' => $product->id]);
        $this->migration()->up();
        $epoch = (int) DB::table('attribute_identity_scopes')->value('write_epoch');
        DB::table('central_categories')->where('id', $category->id)->increment('schema_revision', 0);
        self::assertSame($epoch, (int) DB::table('attribute_identity_scopes')->value('write_epoch'));
        match ($owner) {
            'product' => DB::table('central_products')->where('id', $product->id)->update(['name' => 'Changed product']),
            'translation' => DB::table('product_translations')->where('id', $translation->id)->update(['name' => 'Changed translation']),
            'review' => DB::table('central_categories')->where('id', $category->id)->update(['schema_status' => 'reviewed', 'schema_reviewed_revision' => $category->schema_revision]),
            default => throw new \InvalidArgumentException('Unknown post-cutover owner.'),
        };
        self::assertGreaterThan($epoch, (int) DB::table('attribute_identity_scopes')->value('write_epoch'));
        try {
            $this->migration()->down();
            self::fail('Unsafe inverse accepted');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('Downgrade refused', $error->getMessage());
        }
        self::assertFalse(Schema::hasColumn('attribute_definitions', 'central_category_id'));
    }

    public static function postCutoverOwners(): array
    {
        return [['product'], ['translation'], ['review']];
    }

    public function test_target_sql_write_blocks_unsafe_downgrade(): void
    {
        DB::table('attribute_definitions')->insert(['code' => 'target_write', 'name' => 'Target', 'data_type' => 'string']);
        try {
            $this->migration()->down();
            self::fail('Unsafe inverse accepted');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('Downgrade refused', $error->getMessage());
        }
        self::assertFalse(Schema::hasColumn('attribute_definitions', 'central_category_id'));
        self::assertSame('target_write', AttributeDefinition::query()->sole()->code);
    }

    public function test_unresolved_duplicate_refuses_before_destructive_ddl_then_explicit_dry_run_apply_resolves_distinct_meanings(): void
    {
        $this->migration()->down();
        $a = CentralCategory::factory()->create();
        $b = CentralCategory::factory()->create();
        $actor = User::factory()->centralAdmin()->create();
        $one = DB::table('attribute_definitions')->insertGetId(['central_category_id' => $a->id, 'code' => 'size', 'name' => 'Size', 'data_type' => 'string']);
        $two = DB::table('attribute_definitions')->insertGetId(['central_category_id' => $b->id, 'code' => 'size', 'name' => 'Length', 'data_type' => 'decimal']);
        app(LegacyAttributeBackfill::class)->run();
        $before = DB::table('attribute_definitions')->orderBy('id')->get()->toJson();
        $operatorReport = app(AttributeGlobalizationDiagnosticsQuery::class)->report($actor);
        self::assertFalse($operatorReport['cutover_ready']);
        self::assertArrayHasKey('blockers', $operatorReport);
        self::assertArrayHasKey('write_epoch', $operatorReport);
        try {
            $this->migration()->up();
            self::fail('Unresolved cutover accepted');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('refused before contraction', $error->getMessage());
        }
        self::assertFalse(Schema::hasTable('schema_consumer_rollback_rows'));
        self::assertSame($before, DB::table('attribute_definitions')->orderBy('id')->get()->toJson());
        $plan = ['version' => 1, 'expected_write_epoch' => (int) DB::table('attribute_identity_scopes')->value('write_epoch'), 'definitions' => [
            ['legacy_definition_id' => $one, 'canonical_definition_id' => $one, 'canonical_code' => 'size'],
            ['legacy_definition_id' => $two, 'canonical_definition_id' => $two, 'canonical_code' => 'length'],
        ]];
        $dry = app(AttributeReconciliationWriter::class)->run($plan, false, $actor);
        self::assertTrue($dry['report']['cutover_ready']);
        self::assertSame($before, DB::table('attribute_definitions')->orderBy('id')->get()->toJson());
        $result = app(AttributeReconciliationWriter::class)->run($plan, true, $actor);
        self::assertTrue($result['report']['cutover_ready']);
        self::assertTrue(app(AttributeReconciliationWriter::class)->run($plan, true, $actor)['already_applied']);
        $this->migration()->up();
        self::assertSame(['size', 'length'], AttributeDefinition::query()->orderBy('id')->pluck('code')->all());
        self::assertSame(2, DB::table('attribute_definition_crosswalks')->count());
    }

    public function test_explicit_many_to_one_moves_all_fact_identity_without_recalculating_and_inverse_restores_it(): void
    {
        $this->migration()->down();
        $a = CentralCategory::factory()->create();
        $b = CentralCategory::factory()->create();
        $actor = User::factory()->centralAdmin()->create();
        $target = DB::table('attribute_definitions')->insertGetId(['central_category_id' => $a->id, 'code' => 'colour', 'name' => 'Colour', 'data_type' => 'enum']);
        $source = DB::table('attribute_definitions')->insertGetId(['central_category_id' => $b->id, 'code' => 'colour', 'name' => 'Colour', 'data_type' => 'enum']);
        $targetOption = DB::table('attribute_options')->insertGetId(['attribute_definition_id' => $target, 'code' => 'red', 'label' => 'Red', 'is_visible' => false]);
        $sourceOption = DB::table('attribute_options')->insertGetId(['attribute_definition_id' => $source, 'code' => 'scarlet', 'label' => 'Scarlet', 'is_visible' => false]);
        app(LegacyAttributeBackfill::class)->run();
        $product = CentralProduct::factory()->create(['central_category_id' => $b->id]);
        $valueId = DB::table('central_product_attribute_values')->insertGetId(['central_product_id' => $product->id, 'attribute_definition_id' => $source,
            'value_type' => 'enum', 'raw_value' => 'source scarlet', 'value_enum_code' => 'scarlet', 'confidence' => '0.7500', 'source_type' => 'import', 'source_id' => '7']);
        $factBefore = (array) DB::table('central_product_attribute_values')->where('id', $valueId)->first();
        $draft = NormalizedProductDraft::factory()->create(['category_id' => $b->id, 'attribute_identity_version' => 1,
            'attributes_json' => [['attribute_definition_id' => $source, 'code' => 'colour', 'value_type' => 'enum', 'value_enum_code' => 'scarlet']]]);
        $content = ContentRelation::factory()->create(['related_type' => 'attribute', 'related_id' => $source]);
        $mapping = DB::table('attribute_mappings')->insertGetId(['import_source_id' => ImportSource::factory()->create()->id, 'category_id' => $b->id, 'raw_key' => 'Colour', 'normalized_raw_key' => 'colour', 'attribute_definition_id' => $source,
            'category_attribute_assignment_id' => CategoryAttributeAssignment::query()->where('attribute_definition_id', $source)->sole()->id,
            'status' => 'reviewed', 'confidence' => '0.8750', 'mapping_type' => 'attribute']);
        $locale = Locale::factory()->create();
        $translation = AttributeTranslation::factory()->create(['attribute_definition_id' => $source, 'locale_id' => $locale->id, 'locale' => $locale->code]);
        $translationBefore = $translation->fresh()->getRawOriginal();
        $plan = ['version' => 1, 'expected_write_epoch' => (int) DB::table('attribute_identity_scopes')->value('write_epoch'), 'definitions' => [
            ['legacy_definition_id' => $target, 'canonical_definition_id' => $target, 'canonical_code' => 'colour'],
            ['legacy_definition_id' => $source, 'canonical_definition_id' => $target, 'canonical_code' => 'colour']],
            'options' => [['legacy_option_id' => $sourceOption, 'canonical_option_id' => $targetOption]]];
        self::assertTrue(app(AttributeReconciliationWriter::class)->run($plan, false, $actor)['report']['cutover_ready']);
        app(AttributeReconciliationWriter::class)->run($plan, true, $actor);
        $this->migration()->up();
        $factAfter = (array) DB::table('central_product_attribute_values')->where('id', $valueId)->first();
        self::assertSame($target, $factAfter['attribute_definition_id']);
        self::assertSame('red', $factAfter['value_enum_code']);
        foreach (array_diff(array_keys($factBefore), ['attribute_definition_id', 'value_enum_code']) as $field) {
            self::assertSame($factBefore[$field], $factAfter[$field], $field);
        }
        self::assertSame($target, $content->fresh()->related_id);
        self::assertSame($target, $translation->fresh()->attribute_definition_id);
        $translationAfter = $translation->fresh()->getRawOriginal();
        unset($translationBefore['attribute_definition_id'], $translationAfter['attribute_definition_id']);
        self::assertSame($translationBefore, $translationAfter);
        self::assertSame('reviewed', DB::table('attribute_mappings')->where('id', $mapping)->value('status'));
        self::assertEquals(0.8750, DB::table('attribute_mappings')->where('id', $mapping)->value('confidence'));
        self::assertSame(1, AttributeDefinition::query()->count());
        self::assertSame(2, DB::table('attribute_definition_crosswalks')->count());
        self::assertSame('scarlet', DB::table('attribute_option_crosswalks')->where('legacy_option_id', $sourceOption)->value('legacy_code'));
        self::assertSame(2, $draft->fresh()->attribute_identity_version);
        self::assertSame($target, $draft->fresh()->attributes_json[0]['attribute_definition_id']);
        self::assertTrue(app(SchemaConsumerPreflightV2Query::class)->report()['cutover_ready']);
        $this->migration()->down();
        self::assertSame($factBefore, (array) DB::table('central_product_attribute_values')->where('id', $valueId)->first());
        self::assertSame(2, AttributeDefinition::query()->count());
        self::assertSame(2, DB::table('attribute_options')->count());
        self::assertSame(1, $draft->fresh()->attribute_identity_version);
    }

    public function test_reviewed_flattening_preserves_section_and_translation_identity(): void
    {
        $this->migration()->down();
        $category = CentralCategory::factory()->create();
        $parent = AttributeSection::factory()->for($category, 'category')->create(['position' => 3]);
        $child = AttributeSection::factory()->for($category, 'category')->create(['parent_id' => $parent->id, 'position' => 1]);
        $translation = AttributeSectionTranslation::factory()->create(['attribute_section_id' => $child->id, 'name' => 'Reviewed text']);
        $text = $translation->fresh()->getRawOriginal();
        self::assertArrayHasKey('nested_sections', app(SchemaConsumerPreflightV2Query::class)->report()['blockers']);
        $plan = ['version' => 1, 'expected_write_epoch' => (int) DB::table('attribute_identity_scopes')->value('write_epoch'), 'definitions' => [],
            'sections' => [['section_id' => $parent->id, 'expected_parent_id' => null, 'position' => 0], ['section_id' => $child->id, 'expected_parent_id' => $parent->id, 'position' => 1]]];
        $actor = User::factory()->centralAdmin()->create();
        self::assertTrue(app(AttributeReconciliationWriter::class)->run($plan, false, $actor)['report']['cutover_ready']);
        self::assertSame($parent->id, $child->fresh()->parent_id);
        app(AttributeReconciliationWriter::class)->run($plan, true, $actor);
        self::assertNull($child->fresh()->parent_id);
        self::assertSame($text, $translation->fresh()->getRawOriginal());
        self::assertSame($parent->id, (int) DB::table('schema_consumer_section_decisions')->where('section_id', $child->id)->value('legacy_parent_id'));
        $this->migration()->up();
        self::assertSame(2, AttributeSection::query()->count());
        self::assertSame($text, $translation->fresh()->getRawOriginal());
    }

    public function test_explicit_locale_id_correction_preserves_all_translation_facts_and_old_code(): void
    {
        $this->migration()->down();
        $category = CentralCategory::factory()->create();
        $locale = Locale::factory()->create();
        $previousLocale = Locale::factory()->create();
        $translation = CategoryTranslation::factory()->create(['category_id' => $category->id, 'locale_id' => $previousLocale->id, 'locale' => 'historical-alias', 'name' => 'Preserved translation']);
        $before = $translation->fresh()->getRawOriginal();
        $plan = ['version' => 1, 'expected_write_epoch' => (int) DB::table('attribute_identity_scopes')->value('write_epoch'), 'definitions' => [],
            'translations' => [['owner_table' => 'category_translations', 'translation_id' => $translation->id, 'expected_locale_id' => $previousLocale->id, 'locale_id' => $locale->id]]];
        $actor = User::factory()->centralAdmin()->create();
        self::assertTrue(app(AttributeReconciliationWriter::class)->run($plan, false, $actor)['report']['cutover_ready']);
        self::assertSame($before, $translation->fresh()->getRawOriginal());
        app(AttributeReconciliationWriter::class)->run($plan, true, $actor);
        $after = $translation->fresh()->getRawOriginal();
        unset($before['locale_id'], $before['updated_at'], $after['locale_id'], $after['updated_at']);
        self::assertSame($before, $after);
        self::assertSame($locale->id, $translation->fresh()->locale_id);
        self::assertTrue(app(AttributeReconciliationWriter::class)->run($plan, true, $actor)['already_applied']);
        self::assertSame('locale_id:'.$previousLocale->id.':'.$locale->id, SchemaConsumerDecision::query()->sole()->decision);
        $this->migration()->up();
        self::assertSame('historical-alias', $translation->fresh()->locale);
    }

    public function test_locale_decision_that_collapses_rows_is_atomic_and_never_selects_a_winner(): void
    {
        $this->migration()->down();
        $category = CentralCategory::factory()->create();
        $one = Locale::factory()->create();
        $two = Locale::factory()->create();
        $row = CategoryTranslation::factory()->create(['category_id' => $category->id, 'locale_id' => $one->id, 'locale' => $one->code]);
        CategoryTranslation::factory()->create(['category_id' => $category->id, 'locale_id' => $two->id, 'locale' => $two->code]);
        $before = DB::table('category_translations')->orderBy('id')->get()->toJson();
        $plan = ['version' => 1, 'expected_write_epoch' => (int) DB::table('attribute_identity_scopes')->value('write_epoch'), 'definitions' => [],
            'translations' => [['owner_table' => 'category_translations', 'translation_id' => $row->id, 'expected_locale_id' => $one->id, 'locale_id' => $two->id]]];
        try {
            app(AttributeReconciliationWriter::class)->run($plan, true, User::factory()->centralAdmin()->create());
            self::fail('Translation collision accepted');
        } catch (ValidationException $error) {
            self::assertStringContainsString('no winner', $error->getMessage());
        }
        self::assertSame($before, DB::table('category_translations')->orderBy('id')->get()->toJson());
        self::assertSame(1, $category->fresh()->schema_revision);
        self::assertSame(0, AuditLogEntry::query()->count());
        self::assertSame(0, SchemaConsumerDecision::query()->count());
    }
}
