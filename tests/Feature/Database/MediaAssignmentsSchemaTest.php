<?php

namespace Tests\Feature\Database;

use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MediaAssignmentsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_media_assignments_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('media_assignments'));
        $this->assertTrue(Schema::hasColumns('media_assignments', [
            'id',
            'media_asset_id',
            'entity_type',
            'entity_id',
            'role',
            'position',
            'locale',
            'site_id',
            'market_id',
            'is_primary',
            'visibility',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_media_assignments_has_context_indexes_without_site_or_market_foreign_keys(): void
    {
        $indexes = collect(Schema::getIndexes('media_assignments'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['entity_type', 'entity_id', 'role', 'locale']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['entity_type', 'entity_id', 'role', 'site_id']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['entity_type', 'entity_id', 'role', 'market_id']
        ));
        $this->assertFalse($indexes->contains(
            fn (array $index): bool => $index['name'] === 'media_assignments_entity_type_entity_id_role_index'
        ));

        $foreignKeys = collect(Schema::getForeignKeys('media_assignments'));

        $this->assertFalse($foreignKeys->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['site_id']
        ));
        $this->assertFalse($foreignKeys->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['market_id']
        ));
    }

    public function test_media_assignments_enforces_one_primary_assignment_per_context(): void
    {
        $asset = MediaAsset::factory()->create();
        $otherAsset = MediaAsset::factory()->create();

        MediaAssignment::factory()->for($asset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => 123,
            'role' => 'main',
            'locale' => null,
            'site_id' => null,
            'market_id' => null,
            'is_primary' => true,
        ]);

        $this->expectException(QueryException::class);

        MediaAssignment::factory()->for($otherAsset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => 123,
            'role' => 'main',
            'locale' => null,
            'site_id' => null,
            'market_id' => null,
            'is_primary' => true,
        ]);
    }
}
