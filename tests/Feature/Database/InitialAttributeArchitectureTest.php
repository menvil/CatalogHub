<?php

namespace Tests\Feature\Database;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CategoryComparisonAttribute;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\FacetDefinition;
use App\Models\Imports\AttributeMapping;
use App\Models\Imports\NormalizedProductDraft;
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
