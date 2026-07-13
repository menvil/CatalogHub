<?php

namespace Tests\Feature\Database;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Imports\ImportSource;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttributeMappingsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_attribute_mappings_table_with_review_fields(): void
    {
        $this->assertTrue(Schema::hasTable('attribute_mappings'));
        $this->assertTrue(Schema::hasColumns('attribute_mappings', [
            'id',
            'import_source_id',
            'category_id',
            'raw_key',
            'normalized_raw_key',
            'attribute_definition_id',
            'confidence',
            'status',
            'mapping_type',
            'notes',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_attribute_mapping_has_expected_foreign_keys_and_source_category_key(): void
    {
        $foreignKeys = collect(Schema::getForeignKeys('attribute_mappings'));
        $indexes = collect(Schema::getIndexes('attribute_mappings'));

        foreach ([
            'import_sources' => 'import_source_id',
            'central_categories' => 'category_id',
        ] as $table => $column) {
            $this->assertTrue($foreignKeys->contains(
                fn (array $foreignKey): bool => $foreignKey['columns'] === [$column]
                    && $foreignKey['foreign_table'] === $table
            ));
        }

        $this->assertTrue($foreignKeys->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['attribute_definition_id', 'category_id']
                && $foreignKey['foreign_table'] === 'attribute_definitions'
                && $foreignKey['foreign_columns'] === ['id', 'central_category_id']
                && $foreignKey['on_delete'] === 'cascade'
        ));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['import_source_id', 'category_id', 'raw_key']
        ));
    }

    public function test_database_rejects_attribute_definition_from_another_category(): void
    {
        $source = ImportSource::factory()->create();
        $mappingCategory = CentralCategory::factory()->create();
        $definition = AttributeDefinition::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('attribute_mappings')->insert([
            'import_source_id' => $source->id,
            'category_id' => $mappingCategory->id,
            'raw_key' => 'Power',
            'normalized_raw_key' => 'power',
            'attribute_definition_id' => $definition->id,
            'confidence' => 1,
            'status' => 'reviewed',
            'mapping_type' => 'attribute',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_deleting_category_cascades_its_attribute_mappings(): void
    {
        $source = ImportSource::factory()->create();
        $category = CentralCategory::factory()->create();
        $definition = AttributeDefinition::factory()->for($category, 'category')->create();
        DB::table('attribute_mappings')->insert([
            'import_source_id' => $source->id,
            'category_id' => $category->id,
            'raw_key' => 'Power',
            'normalized_raw_key' => 'power',
            'attribute_definition_id' => $definition->id,
            'confidence' => 1,
            'status' => 'reviewed',
            'mapping_type' => 'attribute',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $category->delete();

        $this->assertDatabaseEmpty('attribute_mappings');
    }

    public function test_deleting_attribute_definition_cascades_its_mapping(): void
    {
        $source = ImportSource::factory()->create();
        $definition = AttributeDefinition::factory()->create();
        DB::table('attribute_mappings')->insert([
            'import_source_id' => $source->id,
            'category_id' => $definition->central_category_id,
            'raw_key' => 'Power',
            'normalized_raw_key' => 'power',
            'attribute_definition_id' => $definition->id,
            'confidence' => 1,
            'status' => 'reviewed',
            'mapping_type' => 'attribute',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $definition->delete();

        $this->assertDatabaseEmpty('attribute_mappings');
    }
}
