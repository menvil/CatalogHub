<?php

namespace Tests\Feature\Database;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CategoryComparisonAttribute;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\FacetDefinition;
use App\Models\Imports\AttributeMapping;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\MeasurementUnit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class InitialAttributeArchitectureTest extends TestCase
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

    public function test_fresh_schema_contains_only_initial_target_ownership(): void
    {
        self::assertSame(['id', 'code', 'name', 'data_type', 'measurement_dimension_id', 'canonical_measurement_unit_id', 'created_at', 'updated_at'], Schema::getColumnListing('attribute_definitions'));
        self::assertSame(['id', 'central_category_id', 'attribute_definition_id', 'attribute_section_id', 'position', 'is_required', 'is_visible', 'is_searchable', 'is_sortable', 'created_at', 'updated_at'], Schema::getColumnListing('category_attribute_assignments'));
        foreach (['attribute_definition_crosswalks', 'attribute_option_crosswalks', 'schema_consumer_decisions', 'schema_consumer_reconciliation_plans', 'schema_consumer_section_decisions', 'schema_consumer_rollback_rows', 'translation_locale_identity_states'] as $table) {
            self::assertFalse(Schema::hasTable($table), $table);
        }
        self::assertSame(['id'], Schema::getColumnListing('attribute_identity_scopes'));
        self::assertFalse(Schema::hasColumn('attribute_sections', 'parent_id'));
        self::assertFalse(Schema::hasColumn('attribute_mappings', 'attribute_definition_id'));
        self::assertFalse(Schema::hasColumn('facet_definitions', 'attribute_definition_id'));
        foreach (['normalized_product_drafts', 'site_product_projections', 'site_category_projections', 'site_search_documents'] as $table) {
            self::assertTrue(Schema::hasColumn($table, 'schema_version'));
            self::assertFalse(Schema::hasColumn($table, 'attribute_identity_version'));
        }
        foreach (['category_translations' => 'category_id', 'product_translations' => 'product_id', 'attribute_translations' => 'attribute_definition_id', 'attribute_option_translations' => 'attribute_option_id', 'attribute_section_translations' => 'attribute_section_id', 'unit_translations' => 'measurement_unit_id'] as $table => $owner) {
            $indexes = collect(Schema::getIndexes($table));
            self::assertTrue($indexes->contains(fn ($index) => $index['unique'] && $index['columns'] === [$owner, 'locale_id']), $table);
            self::assertFalse($indexes->contains(fn ($index) => $index['unique'] && $index['columns'] === [$owner, 'locale']), $table);
        }
    }

    public function test_target_factories_require_no_bootstrap_transition(): void
    {
        $definition = AttributeDefinition::factory()->create();
        $a = CategoryAttributeAssignment::factory()->for($definition, 'definition')->create();
        $b = CategoryAttributeAssignment::factory()->for($definition, 'definition')->create();
        self::assertNotSame($a->central_category_id, $b->central_category_id);
        $fact = CentralProductAttributeValue::factory()->forAssignment($a)->create();
        self::assertSame($a->central_category_id, $fact->product->central_category_id);
        $mapping = AttributeMapping::factory()->create();
        self::assertSame($mapping->category_id, $mapping->assignment->central_category_id);
        $facet = FacetDefinition::factory()->boolean()->create();
        self::assertSame($facet->category_id, $facet->assignment->central_category_id);
        $comparison = CategoryComparisonAttribute::factory()->create();
        self::assertSame($comparison->central_category_id, $comparison->assignment->central_category_id);
        self::assertSame(1, NormalizedProductDraft::factory()->create()->schema_version);
        if (DB::getDriverName() === 'sqlite') {
            self::assertSame([], DB::select('PRAGMA foreign_key_check'));
        }
    }

    public function test_definition_deletion_cannot_cascade_durable_product_facts(): void
    {
        $keys = collect(Schema::getForeignKeys('central_product_attribute_values'));
        $definitionKey = $keys->firstWhere('columns', ['attribute_definition_id']);
        $productKey = $keys->firstWhere('columns', ['central_product_id']);
        self::assertNotNull($definitionKey);
        self::assertSame('attribute_definitions', $definitionKey['foreign_table']);
        self::assertContains(strtolower($definitionKey['on_delete']), ['restrict', 'no action']);
        self::assertNotNull($productKey);
        self::assertSame('cascade', strtolower($productKey['on_delete']));

        $unit = MeasurementUnit::factory()->create();
        $definition = AttributeDefinition::factory()->create(['data_type' => 'decimal',
            'measurement_dimension_id' => $unit->dimension_id, 'canonical_measurement_unit_id' => $unit->id]);
        $assignment = CategoryAttributeAssignment::factory()->for($definition, 'definition')->create();
        $fact = CentralProductAttributeValue::factory()->forAssignment($assignment)->create([
            'raw_value' => '55.125 measured', 'value_text' => null, 'value_number' => '55.125000',
            'value_min' => '55.000000', 'value_max' => '55.250000', 'source_unit' => $unit->code,
            'canonical_value' => '55.125000', 'canonical_unit' => $unit->code, 'confidence' => '0.9750',
            'source_type' => 'import', 'source_id' => 'durable-fact',
            'source_reference' => ['row' => 7, 'source' => 'fixture'],
        ]);
        $before = (array) DB::table('central_product_attribute_values')->where('id', $fact->id)->first();

        // First prove the ordinary assigned fixture rejects direct SQL deletion.
        foreach ([false, true] as $withoutAssignment) {
            if ($withoutAssignment) {
                // Isolate the fact FK: assignment RESTRICT must not mask a fact CASCADE regression.
                DB::table('category_attribute_assignments')->where('id', $assignment->id)->delete();
            }
            try {
                DB::transaction(fn () => DB::table('attribute_definitions')->where('id', $definition->id)->delete());
                self::fail('The database must reject deleting a definition with durable Product facts.');
            } catch (QueryException) {
                self::assertTrue(AttributeDefinition::query()->whereKey($definition->id)->exists());
                self::assertSame($before, (array) DB::table('central_product_attribute_values')->where('id', $fact->id)->first());
            }
        }
    }

    public function test_product_deletion_still_cascades_its_own_facts(): void
    {
        $assignment = CategoryAttributeAssignment::factory()->create();
        $fact = CentralProductAttributeValue::factory()->forAssignment($assignment)->create();
        DB::table('central_products')->where('id', $fact->central_product_id)->delete();
        self::assertFalse(CentralProductAttributeValue::query()->whereKey($fact->id)->exists());
        self::assertTrue(AttributeDefinition::query()->whereKey($assignment->attribute_definition_id)->exists());
    }

    public function test_initial_migration_history_can_reset_and_recreate_without_finalization(): void
    {
        $this->artisan('migrate:reset', ['--force' => true])->assertSuccessful();
        self::assertFalse(Schema::hasTable('attribute_definitions'));
        self::assertFalse(Schema::hasTable('category_attribute_assignments'));
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        self::assertTrue(Schema::hasTable('attribute_definitions'));
        self::assertTrue(Schema::hasTable('category_attribute_assignments'));
        self::assertSame(1, DB::table('attribute_identity_scopes')->count());
        self::assertSame(1, NormalizedProductDraft::factory()->create()->schema_version);
    }
}
