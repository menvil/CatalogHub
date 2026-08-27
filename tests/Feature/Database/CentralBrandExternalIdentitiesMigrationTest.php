<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\Imports\CentralBrandExternalIdentity;
use App\Models\Imports\ImportSource;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class CentralBrandExternalIdentitiesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_enforces_provenance_without_brand_shortcut_columns(): void
    {
        self::assertTrue(Schema::hasColumns('central_brand_external_identities', [
            'id', 'central_brand_id', 'import_source_id', 'external_id', 'external_id_hash',
            'external_url', 'created_at', 'updated_at',
        ]));
        foreach (['external_id', 'source_id', 'import_source_id'] as $column) {
            self::assertFalse(Schema::hasColumn('central_brands', $column));
        }

        $indexes = collect(Schema::getIndexes('central_brand_external_identities'));
        self::assertTrue($indexes->contains(
            static fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['import_source_id', 'external_id_hash'],
        ));
        self::assertTrue($indexes->contains(static fn (array $index): bool => $index['columns'] === ['central_brand_id']));
        self::assertTrue($indexes->contains(static fn (array $index): bool => $index['columns'] === ['import_source_id']));
    }

    public function test_source_namespace_is_case_sensitive_and_foreign_keys_cascade_or_restrict(): void
    {
        $brand = CentralBrand::factory()->create();
        $source = ImportSource::factory()->create();
        CentralBrandExternalIdentity::factory()->for($brand, 'brand')->for($source, 'source')->externalId('ABC')->create();
        CentralBrandExternalIdentity::factory()->for($brand, 'brand')->for($source, 'source')->externalId('abc')->create();
        self::assertDatabaseCount('central_brand_external_identities', 2);

        try {
            DB::transaction(
                static fn (): int => DB::table('import_sources')->where('id', $source->id)->delete(),
                attempts: 1,
            );
            self::fail('Expected ImportSource deletion to be restricted while provenance exists.');
        } catch (QueryException) {
            self::assertDatabaseHas('import_sources', ['id' => $source->id]);
        }

        $brand->delete();
        self::assertDatabaseCount('central_brand_external_identities', 0);
        self::assertDatabaseHas('import_sources', ['id' => $source->id]);
    }
}
