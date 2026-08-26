<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\CentralCatalog\CatalogTag;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class CatalogTagsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_has_only_the_global_vocabulary_and_explicit_brand_pivot_contract(): void
    {
        self::assertTrue(Schema::hasColumns('catalog_tags', [
            'id', 'name', 'normalized_name', 'normalized_name_hash', 'created_at', 'updated_at',
        ]));
        self::assertFalse(Schema::hasColumn('catalog_tags', 'slug'));
        self::assertFalse(Schema::hasColumn('catalog_tags', 'status'));
        self::assertTrue(Schema::hasColumns('central_brand_tag', ['central_brand_id', 'catalog_tag_id']));
        self::assertFalse(Schema::hasTable('taggables'));
        self::assertFalse(Schema::hasTable('brand_category'));
        self::assertFalse(Schema::hasTable('central_brand_category'));

        $uniqueIndexes = collect(Schema::getIndexes('catalog_tags'))
            ->filter(static fn (array $index): bool => $index['unique'] === true)
            ->pluck('columns')
            ->all();
        self::assertContains(['normalized_name_hash'], $uniqueIndexes);

        $pivotIndexes = collect(Schema::getIndexes('central_brand_tag'))->pluck('columns')->all();
        self::assertContains(['central_brand_id', 'catalog_tag_id'], $pivotIndexes);

        $brand = CentralBrand::factory()->create();
        $tag = CatalogTag::factory()->create();
        DB::table('central_brand_tag')->insert([
            'central_brand_id' => $brand->id,
            'catalog_tag_id' => $tag->id,
        ]);

        try {
            DB::transaction(static function () use ($brand, $tag): void {
                DB::table('central_brand_tag')->insert([
                    'central_brand_id' => $brand->id,
                    'catalog_tag_id' => $tag->id,
                ]);
            });
            self::fail('Expected Brand tag pivot uniqueness failure.');
        } catch (QueryException) {
            self::assertDatabaseCount('central_brand_tag', 1);
        }
    }

    public function test_database_enforces_tag_identity_and_pivot_uniqueness_and_cascades_pivot_deletes(): void
    {
        $brand = CentralBrand::factory()->create();
        $tag = CatalogTag::factory()->create(['name' => 'Premium']);
        $brand->tags()->attach($tag);

        try {
            DB::transaction(static function () use ($tag): void {
                DB::table('catalog_tags')->insert([
                    'name' => 'premium',
                    'normalized_name' => $tag->normalized_name,
                    'normalized_name_hash' => $tag->normalized_name_hash,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
            self::fail('Expected normalized identity uniqueness failure.');
        } catch (QueryException) {
            self::assertSame(1, CatalogTag::query()->count());
        }

        $brand->delete();
        self::assertDatabaseCount('central_brand_tag', 0);
        self::assertDatabaseCount('catalog_tags', 1);

        $secondBrand = CentralBrand::factory()->create();
        $secondBrand->tags()->attach($tag);
        $tag->delete();
        self::assertDatabaseCount('central_brand_tag', 0);
    }
}
