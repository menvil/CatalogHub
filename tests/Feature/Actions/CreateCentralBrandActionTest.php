<?php

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\CreateCentralBrandAction;
use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateCentralBrandActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_draft_brand_with_minimal_valid_input_and_generated_slug(): void
    {
        $brand = app(CreateCentralBrandAction::class)->handle(['name' => 'Samsung Electronics']);

        $this->assertTrue($brand->exists);
        $this->assertSame('Samsung Electronics', $brand->name);
        $this->assertSame('samsung-electronics', $brand->slug);
        $this->assertSame(CentralBrandStatus::Draft, $brand->status);
        $this->assertNull($brand->website_url);
        $this->assertNull($brand->country_code);
        $this->assertSame($brand->getKey(), $brand->fresh()->getKey());
    }

    public function test_normalizes_all_canonical_create_input(): void
    {
        $brand = app(CreateCentralBrandAction::class)->handle([
            'name' => '  Samsung   Electronics  ',
            'slug' => ' Samsung_Phones ',
            'website_url' => '  https://www.samsung.com/en-us/  ',
            'country_code' => ' kr ',
        ]);

        $this->assertSame('Samsung Electronics', $brand->name);
        $this->assertSame('samsung-phones', $brand->slug);
        $this->assertSame('https://www.samsung.com/en-us/', $brand->website_url);
        $this->assertSame('KR', $brand->country_code);
        $this->assertSame(CentralBrandStatus::Draft, $brand->status);
    }

    public function test_generated_slug_handles_ampersands(): void
    {
        $brand = app(CreateCentralBrandAction::class)->handle(['name' => 'Bang & Olufsen']);

        $this->assertSame('bang-olufsen', $brand->slug);
    }

    public function test_preserves_unicode_and_canonical_name_case_when_an_ascii_slug_is_supplied(): void
    {
        $brand = app(CreateCentralBrandAction::class)->handle([
            'name' => '华为',
            'slug' => 'huawei',
        ]);

        $this->assertSame('华为', $brand->name);
    }

    public function test_blank_nullable_metadata_is_stored_as_null(): void
    {
        $brand = app(CreateCentralBrandAction::class)->handle([
            'name' => 'Logitech',
            'website_url' => '   ',
            'country_code' => '   ',
        ]);

        $this->assertNull($brand->website_url);
        $this->assertNull($brand->country_code);
    }

    #[DataProvider('invalidNameProvider')]
    public function test_rejects_invalid_names(string $name): void
    {
        $this->assertValidationError('name', fn () => app(CreateCentralBrandAction::class)->handle([
            'name' => $name,
        ]));

        $this->assertDatabaseCount('central_brands', 0);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidNameProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace only' => ['   '];
        yield 'longer than storage contract' => [str_repeat('A', 256)];
    }

    public function test_rejects_an_invalid_explicit_slug_as_a_field_validation_error(): void
    {
        $this->assertValidationError('slug', fn () => app(CreateCentralBrandAction::class)->handle([
            'name' => 'Samsung',
            'slug' => 'Samsung!!!',
        ]));

        $this->assertDatabaseCount('central_brands', 0);
    }

    public function test_rejects_a_name_that_cannot_generate_an_ascii_slug(): void
    {
        $this->assertValidationError('slug', fn () => app(CreateCentralBrandAction::class)->handle([
            'name' => '💻',
        ]));

        $this->assertDatabaseCount('central_brands', 0);
    }

    public function test_rejects_a_duplicate_slug_before_creating_a_second_row(): void
    {
        CentralBrand::factory()->create(['slug' => 'samsung']);

        $this->assertValidationError('slug', fn () => app(CreateCentralBrandAction::class)->handle([
            'name' => 'Samsung New',
            'slug' => 'samsung',
        ]));

        $this->assertDatabaseCount('central_brands', 1);
    }

    #[DataProvider('duplicateNameProvider')]
    public function test_rejects_exact_case_insensitive_normalized_duplicate_names(string $duplicate): void
    {
        CentralBrand::factory()->create(['name' => 'Samsung', 'slug' => 'samsung']);

        $this->assertValidationError('name', fn () => app(CreateCentralBrandAction::class)->handle([
            'name' => $duplicate,
            'slug' => 'different-slug',
        ]));

        $this->assertDatabaseCount('central_brands', 1);
    }

    /** @return iterable<string, array{string}> */
    public static function duplicateNameProvider(): iterable
    {
        yield 'lowercase' => ['samsung'];
        yield 'uppercase' => ['SAMSUNG'];
        yield 'surrounding whitespace' => [' Samsung '];
    }

    public function test_allows_similar_but_non_identical_brand_names(): void
    {
        CentralBrand::factory()->create(['name' => 'Samsung', 'slug' => 'samsung']);

        app(CreateCentralBrandAction::class)->handle(['name' => 'Samsung Electronics']);
        app(CreateCentralBrandAction::class)->handle(['name' => 'Samsung Display']);

        $this->assertDatabaseCount('central_brands', 3);
    }

    public function test_duplicate_guard_compares_names_after_internal_whitespace_is_collapsed(): void
    {
        CentralBrand::factory()->create([
            'name' => 'Samsung Electronics',
            'slug' => 'samsung-electronics',
        ]);

        $this->assertValidationError('name', fn () => app(CreateCentralBrandAction::class)->handle([
            'name' => 'Samsung   Electronics',
            'slug' => 'different-slug',
        ]));
    }

    #[DataProvider('invalidWebsiteProvider')]
    public function test_rejects_invalid_or_non_http_website_urls(string $websiteUrl): void
    {
        $this->assertValidationError('website_url', fn () => app(CreateCentralBrandAction::class)->handle([
            'name' => 'Sony',
            'website_url' => $websiteUrl,
        ]));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidWebsiteProvider(): iterable
    {
        yield 'missing scheme' => ['samsung.com'];
        yield 'www only' => ['www.samsung.com'];
        yield 'javascript' => ['javascript:alert(1)'];
        yield 'ftp' => ['ftp://example.com'];
        yield 'not a URL' => ['not-a-url'];
        yield 'longer than storage contract' => ['https://example.com/'.str_repeat('a', 237)];
    }

    #[DataProvider('invalidCountryCodeProvider')]
    public function test_rejects_structurally_invalid_country_codes(string $countryCode): void
    {
        $this->assertValidationError('country_code', fn () => app(CreateCentralBrandAction::class)->handle([
            'name' => 'Sony',
            'country_code' => $countryCode,
        ]));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidCountryCodeProvider(): iterable
    {
        yield 'three letters' => ['KOR'];
        yield 'one letter' => ['K'];
        yield 'digits' => ['12'];
        yield 'mixed letter and digit' => ['U1'];
        yield 'country name' => ['ukraine'];
        yield 'non ASCII' => ['БГ'];
    }

    public function test_rejects_status_and_other_unsupported_write_fields(): void
    {
        foreach ([
            ['name' => 'Draft Brand', 'status' => CentralBrandStatus::Draft->value],
            ['name' => 'Active Brand', 'status' => CentralBrandStatus::Active->value],
            ['name' => 'Archived Brand', 'status' => CentralBrandStatus::Archived->value],
            ['name' => 'Brand With ID', 'id' => 99],
        ] as $input) {
            $field = array_key_exists('status', $input) ? 'status' : 'id';
            $this->assertValidationError($field, fn () => app(CreateCentralBrandAction::class)->handle($input));
        }

        $this->assertDatabaseCount('central_brands', 0);
    }

    private function assertValidationError(string $field, Closure $callback): void
    {
        try {
            $callback();
            $this->fail("Expected validation to fail for {$field}.");
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }
}
