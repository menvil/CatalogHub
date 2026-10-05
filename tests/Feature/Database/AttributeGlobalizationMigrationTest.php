<?php

namespace Tests\Feature\Database;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\ContentRelation;
use App\Models\FacetDefinition;
use App\Models\Imports\ImportSource;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Locale;
use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use App\Models\Translations\AttributeTranslation;
use App\Models\User;
use App\Queries\Attributes\AttributeGlobalizationDiagnosticsQuery;
use App\Services\AttributeGlobalization\LegacyAttributeBackfill;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class AttributeGlobalizationMigrationTest extends TestCase
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

    public function test_representative_legacy_inventory_is_lossless_idempotent_reports_conflicts_and_reverses_before_new_writes(): void
    {
        $localeMigration = require database_path('migrations/2026_10_05_000002_expand_translation_locale_identity.php');
        $migration = require database_path('migrations/2026_10_05_000001_expand_global_attribute_ownership.php');
        $localeMigration->down();
        $migration->down();
        $actor = User::factory()->centralAdmin()->create();
        $a = CentralCategory::factory()->create();
        $b = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($a, 'category')->create();
        $dimension = MeasurementDimension::factory()->create(['code' => 'length']);
        $unit = MeasurementUnit::factory()->for($dimension, 'dimension')->create(['code' => 'inch']);
        $otherUnit = MeasurementUnit::factory()->create(['code' => 'kg']);
        $definitions = [];
        $create = function (CentralCategory $category, string $code, array $data = []) use (&$definitions): AttributeDefinition {
            $definition = AttributeDefinition::factory()->for($category, 'category')->create(['code' => $code, 'name' => $code, 'position' => 9, 'is_required' => true, 'is_visible' => false, 'is_searchable' => true, 'is_sortable' => true, 'is_filterable' => true, 'is_comparable' => true, ...$data]);
            $definitions[] = $definition;

            return $definition;
        };
        $one = $create($a, 'screen_size', ['data_type' => 'decimal', 'dimension' => 'length', 'canonical_unit' => 'inch', 'attribute_section_id' => $section->id]);
        $two = $create($b, 'screen_size', ['data_type' => 'decimal', 'dimension' => 'length', 'canonical_unit' => 'inch']);
        $create($a, 'same_type', ['data_type' => 'integer']);
        $create($b, 'same_type', ['data_type' => 'boolean']);
        $create($a, 'same_measurement', ['data_type' => 'decimal', 'dimension' => 'length', 'canonical_unit' => 'inch']);
        $create($b, 'same_measurement', ['data_type' => 'decimal', 'dimension' => 'length', 'canonical_unit' => 'kg']);
        $enum = $create($a, 'display', ['data_type' => 'enum']);
        $enumOther = $create($b, 'display', ['data_type' => 'enum']);
        $option = AttributeOption::factory()->for($enum, 'attribute')->create(['code' => 'OLED', 'label' => 'OLED']);
        AttributeOption::factory()->for($enumOther, 'attribute')->create(['code' => 'LCD', 'label' => 'LCD']);
        $multi = $create($a, 'connectors', ['data_type' => 'multi_enum']);
        AttributeOption::factory()->for($multi, 'attribute')->create(['code' => 'HDMI']);
        $create($a, 'alternative_code', ['name' => 'screen_size', 'data_type' => 'decimal', 'dimension' => 'length', 'canonical_unit' => 'inch']);
        $create($a, 'unknown_measurement', ['data_type' => 'decimal', 'dimension' => 'unknown', 'canonical_unit' => 'unknown']);
        $create($b, 'incomplete_measurement', ['data_type' => 'decimal', 'dimension' => 'length']);
        $create($b, 'measured_text', ['data_type' => 'string', 'dimension' => 'length', 'canonical_unit' => 'inch']);
        $product = CentralProduct::factory()->for($a, 'category')->create();
        foreach ([[$one, 'decimal', ['value_number' => '42.125', 'raw_value' => '42 1/8 inches', 'value_min' => '42', 'value_max' => '43', 'source_unit' => 'inch', 'canonical_value' => '1.069975', 'canonical_unit' => 'm']], [$enum, 'enum', ['value_enum_code' => 'OLED', 'raw_value' => 'organic LED']], [$multi, 'multi_enum', ['value_json' => ['HDMI'], 'raw_value' => 'HDMI']]] as [$definition, $type, $data]) {
            CentralProductAttributeValue::factory()->create(['central_product_id' => $product->id, 'attribute_definition_id' => $definition->id, 'value_type' => $type, 'confidence' => '0.7321', 'source_type' => 'import', 'source_id' => 'supplier-42', 'source_reference' => ['batch' => 19], ...$data]);
        }
        $locale = Locale::factory()->create();
        AttributeTranslation::factory()->for($one, 'attributeDefinition')->create(['locale_id' => $locale->id, 'locale' => $locale->code, 'label' => 'Diagonal', 'status' => 'approved', 'source_hash' => 'unchanged-source', 'approved_by_user_id' => $actor->id, 'approved_at' => '2026-09-01 12:00:00']);
        AttributeTranslation::factory()->for($one, 'attributeDefinition')->create(['locale_id' => $locale->id, 'locale' => 'historic-code', 'label' => 'Conflicting copy', 'status' => 'human_reviewed']);
        AttributeTranslation::factory()->for($two, 'attributeDefinition')->create(['locale_id' => $locale->id, 'locale' => $locale->code, 'label' => 'Screen width']);
        DB::table('attribute_display_rules')->insert(['attribute_definition_id' => $one->id, 'market_code' => 'US', 'locale' => $locale->code, 'display_unit_id' => $unit->id, 'decimals' => 2, 'rounding_mode' => 'half_up', 'created_at' => now(), 'updated_at' => now()]);
        $source = ImportSource::factory()->create();
        foreach ([['attribute_definition_id' => $one->id, 'raw_key' => 'Size', 'status' => 'reviewed'], ['attribute_definition_id' => null, 'raw_key' => 'Unmapped', 'status' => 'auto']] as $data) {
            DB::table('attribute_mappings')->insert(['import_source_id' => $source->id, 'category_id' => $a->id, 'normalized_raw_key' => strtolower($data['raw_key']), 'confidence' => '0.8750', 'mapping_type' => 'attribute', 'notes' => 'preserve', 'created_at' => now(), 'updated_at' => now(), ...$data]);
        }
        NormalizedProductDraft::factory()->create(['category_id' => $a->id, 'attributes_json' => [['attribute_definition_id' => $enum->id, 'code' => 'display', 'value' => 'OLED', 'metadata' => ['option_id' => $option->id]], ['code' => 'screen_size', 'value_type' => 'decimal', 'value' => 42.125, 'source_unit' => 'inch']]]);
        FacetDefinition::factory()->create(['category_id' => $a->id, 'attribute_definition_id' => $one->id]);
        ContentRelation::factory()->create(['related_type' => 'attribute', 'related_id' => $one->id]);
        $tables = ['attribute_definitions', 'attribute_options', 'central_product_attribute_values', 'attribute_translations', 'attribute_mappings', 'normalized_product_drafts', 'attribute_display_rules', 'facet_definitions', 'content_relations'];
        $before = [];
        foreach ($tables as $table) {
            $before[$table] = $this->rows($table);
        }
        $this->withoutLiveBackfill(fn () => $migration->up());
        $localeMigration->up();
        foreach ($tables as $table) {
            foreach ($this->rows($table) as $index => $row) {
                self::assertSame($before[$table][$index], array_intersect_key($row, $before[$table][$index]), $table.' lost or changed legacy fields');
            }
            self::assertCount(count($before[$table]), $this->rows($table));
        }
        self::assertSame(count($definitions), DB::table('category_attribute_assignments')->count());
        foreach ($definitions as $definition) {
            $assignment = (array) DB::table('category_attribute_assignments')->where('attribute_definition_id', $definition->id)->first();
            self::assertSame($definition->central_category_id, $assignment['central_category_id']);
            foreach (LegacyAttributeBackfill::LOCAL_FIELDS as $field) {
                self::assertEquals($definition->getRawOriginal($field), $assignment[$field]);
            }
            self::assertArrayNotHasKey('is_filterable', $assignment);
            self::assertArrayNotHasKey('is_comparable', $assignment);
        }
        self::assertSame($dimension->id, (int) $one->fresh()->measurement_dimension_id);
        self::assertNull(DB::table('attribute_mappings')->where('raw_key', 'Unmapped')->value('category_attribute_assignment_id'));
        self::assertNotNull(DB::table('attribute_mappings')->where('raw_key', 'Size')->value('category_attribute_assignment_id'));
        self::assertSame(0, (int) DB::table('translation_locale_identity_states')->where('owner_table', 'attribute_translations')->value('unique_installed'));
        $query = app(AttributeGlobalizationDiagnosticsQuery::class);
        $report = $query->report($actor);
        self::assertFalse($report['cutover_ready']);
        self::assertCount(4, $report['globally_duplicate_codes']);
        self::assertCount(4, $report['measurement_mapping_failures']);
        self::assertCount(1, $report['translation_locale_collisions']);
        self::assertCount(1, $report['option_code_conflicts']);
        self::assertSame([], $report['product_membership_problems']);
        self::assertSame([], $report['mapping_membership_problems']);
        self::assertCount(1, $report['draft_non_fk_references']);
        $stableOutput = json_encode($report, JSON_THROW_ON_ERROR);
        app(LegacyAttributeBackfill::class)->run();
        self::assertSame($stableOutput, json_encode($query->report($actor), JSON_THROW_ON_ERROR));
        $this->artisan('catalog:diagnose-attribute-globalization', ['--actor' => $actor->id])->assertExitCode(1);
        $localeMigration->down();
        $this->withoutLiveBackfill(fn () => $migration->down());
        foreach ($tables as $table) {
            self::assertSame($before[$table], $this->rows($table), $table.' downgrade changed facts');
        }
        self::assertFalse(Schema::hasTable('category_attribute_assignments'));
        $migration->up();
        $localeMigration->up();
    }

    public function test_frozen_backfill_preserves_inventory_across_batch_boundaries(): void
    {
        $localeMigration = require database_path('migrations/2026_10_05_000002_expand_translation_locale_identity.php');
        $migration = require database_path('migrations/2026_10_05_000001_expand_global_attribute_ownership.php');
        $localeMigration->down();
        $migration->down();
        $a = CentralCategory::factory()->create();
        $b = CentralCategory::factory()->create();
        $source = ImportSource::factory()->create();
        $timestamp = now()->toDateTimeString();
        $rows = [];
        for ($index = 0; $index <= 500; $index++) {
            $rows[] = ['central_category_id' => $index === 500 ? $b->id : $a->id, 'code' => in_array($index, [0, 500], true) ? 'collision' : 'batch_'.$index,
                'name' => 'Batch', 'data_type' => 'enum', 'position' => $index, 'is_required' => $index % 2, 'is_visible' => true, 'is_searchable' => false, 'is_sortable' => true,
                'created_at' => $timestamp, 'updated_at' => $timestamp];
        }
        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('attribute_definitions')->insert($chunk);
        }
        $options = [];
        $mappings = [];
        foreach (DB::table('attribute_definitions')->orderBy('id')->get() as $definition) {
            $options[] = ['attribute_definition_id' => $definition->id, 'code' => 'yes', 'label' => 'Yes', 'position' => 0, 'is_visible' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp];
            $mappings[] = ['import_source_id' => $source->id, 'category_id' => $definition->central_category_id, 'attribute_definition_id' => $definition->id,
                'raw_key' => 'raw_'.$definition->id, 'normalized_raw_key' => 'raw_'.$definition->id, 'mapping_type' => 'attribute', 'status' => 'reviewed', 'confidence' => '0.8750', 'created_at' => $timestamp, 'updated_at' => $timestamp];
        }
        foreach (array_chunk($options, 250) as $chunk) {
            DB::table('attribute_options')->insert($chunk);
        }
        foreach (array_chunk($mappings, 250) as $chunk) {
            DB::table('attribute_mappings')->insert($chunk);
        }
        $before = [];
        foreach (['attribute_definitions', 'attribute_options', 'attribute_mappings'] as $table) {
            $before[$table] = $this->rows($table);
        }
        $this->withoutLiveBackfill(fn () => $migration->up());
        $localeMigration->up();
        self::assertSame(501, DB::table('category_attribute_assignments')->count());
        self::assertSame(501, DB::table('attribute_definition_crosswalks')->count());
        self::assertSame(501, DB::table('attribute_option_crosswalks')->count());
        self::assertSame(501, DB::table('attribute_mappings')->whereNotNull('category_attribute_assignment_id')->count());
        self::assertSame(2, DB::table('attribute_definitions')->whereNull('canonical_code')->count());
        $localeMigration->down();
        $this->withoutLiveBackfill(fn () => $migration->down());
        foreach ($before as $table => $rows) {
            self::assertSame($rows, $this->rows($table));
        }
        $migration->up();
        $localeMigration->up();
    }

    /** @return list<array<string, mixed>> */
    private function rows(string $table): array
    {
        return DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    }

    private function withoutLiveBackfill(callable $operation): void
    {
        $service = app(LegacyAttributeBackfill::class);
        $this->app->bind(LegacyAttributeBackfill::class, fn () => throw new \RuntimeException('Historical migration resolved the live backfill service.'));
        try {
            $operation();
        } finally {
            $this->app->instance(LegacyAttributeBackfill::class, $service);
        }
    }
}
