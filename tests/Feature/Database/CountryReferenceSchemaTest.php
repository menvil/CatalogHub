<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\Geography\Country;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CountryReference;
use Tests\TestCase;

final class CountryReferenceSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_and_translation_schema_matches_reference_contract(): void
    {
        $this->assertTrue(Schema::hasColumns('countries', [
            'id', 'alpha2', 'alpha3', 'numeric_code', 'canonical_name', 'region_code',
            'subregion_code', 'intermediate_region_code', 'is_active', 'created_at', 'updated_at',
        ]));
        $this->assertTrue(Schema::hasColumns('country_translations', [
            'id', 'country_id', 'locale', 'name', 'created_at', 'updated_at',
        ]));
        $this->assertSame('004', CountryReference::get('AF')->numeric_code);
    }

    public function test_brand_country_foreign_key_restricts_country_deletion(): void
    {
        $country = CountryReference::get('KR');
        $brand = CentralBrand::factory()->create(['country_id' => $country->id]);

        $this->expectException(QueryException::class);
        $country->delete();
    }

    #[DataProvider('uniqueIdentityProvider')]
    public function test_country_external_identities_are_database_unique(string $field, string $value): void
    {
        $attributes = [
            'alpha2' => 'QZ',
            'alpha3' => 'QZZ',
            'numeric_code' => '998',
            'canonical_name' => 'Unique Constraint Probe',
            'is_active' => true,
            $field => $value,
        ];

        $this->expectException(QueryException::class);
        Country::query()->create($attributes);
    }

    /** @return iterable<string, array{string, string}> */
    public static function uniqueIdentityProvider(): iterable
    {
        yield 'alpha2' => ['alpha2', 'DE'];
        yield 'alpha3' => ['alpha3', 'DEU'];
        yield 'numeric' => ['numeric_code', '276'];
    }
}
