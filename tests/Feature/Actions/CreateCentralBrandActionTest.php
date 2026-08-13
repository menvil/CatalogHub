<?php

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\CreateCentralBrandAction;
use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\AssertsValidationErrors;
use Tests\TestCase;

class CreateCentralBrandActionTest extends TestCase
{
    use AssertsValidationErrors;
    use RefreshDatabase;

    public function test_creates_a_draft_brand_with_minimal_valid_input_and_generated_slug(): void
    {
        $brand = app(CreateCentralBrandAction::class)->handle(['name' => 'Samsung Electronics']);

        $this->assertTrue($brand->exists);
        $this->assertSame('Samsung Electronics', $brand->name);
        $this->assertSame('samsung electronics', $brand->normalized_name);
        $this->assertSame(hash('sha256', 'samsung electronics'), $brand->normalized_name_hash);
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
        $this->assertSame('huawei', $brand->slug);
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

    public function test_collects_duplicate_name_and_slug_errors_in_one_validation_response(): void
    {
        CentralBrand::factory()->create(['name' => 'Samsung', 'slug' => 'samsung']);

        try {
            app(CreateCentralBrandAction::class)->handle([
                'name' => 'SAMSUNG',
                'slug' => 'samsung',
            ]);
            $this->fail('Duplicate name and slug input was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('name', $exception->errors());
            $this->assertArrayHasKey('slug', $exception->errors());
        }

        $this->assertDatabaseCount('central_brands', 1);
    }

    #[DataProvider('duplicateNameProvider')]
    public function test_rejects_exact_case_insensitive_normalized_duplicate_names(string $existing, string $duplicate): void
    {
        CentralBrand::factory()->create(['name' => $existing, 'slug' => 'existing-brand']);

        $this->assertValidationError('name', fn () => app(CreateCentralBrandAction::class)->handle([
            'name' => $duplicate,
            'slug' => 'different-slug',
        ]));

        $this->assertDatabaseCount('central_brands', 1);
    }

    /** @return iterable<string, array{string, string}> */
    public static function duplicateNameProvider(): iterable
    {
        yield 'lowercase' => ['Samsung', 'samsung'];
        yield 'uppercase' => ['Samsung', 'SAMSUNG'];
        yield 'surrounding whitespace' => ['Samsung', ' Samsung '];
        yield 'Unicode case fold' => ['ÉLECTRO', 'électro'];
        yield 'canonically equivalent Unicode' => ['ÉLECTRO', "e\u{0301}lectro"];
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

    public function test_unicode_duplicate_guard_remains_accent_sensitive(): void
    {
        CentralBrand::factory()->create(['name' => 'ÉLECTRO', 'slug' => 'electro-accented']);

        $brand = app(CreateCentralBrandAction::class)->handle([
            'name' => 'Electro',
            'slug' => 'electro',
        ]);

        $this->assertSame('Electro', $brand->name);
        $this->assertDatabaseCount('central_brands', 2);
    }

    #[DataProvider('invalidWebsiteProvider')]
    public function test_rejects_invalid_or_non_http_website_urls(string $websiteUrl): void
    {
        $this->assertValidationError('website_url', fn () => app(CreateCentralBrandAction::class)->handle([
            'name' => 'Sony',
            'website_url' => $websiteUrl,
        ]));

        $this->assertDatabaseCount('central_brands', 0);
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

        $this->assertDatabaseCount('central_brands', 0);
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

    #[DataProvider('unsupportedFieldProvider')]
    public function test_rejects_status_and_other_unsupported_write_fields(array $input, string $field): void
    {
        $this->assertValidationError($field, fn () => app(CreateCentralBrandAction::class)->handle($input));

        $this->assertDatabaseCount('central_brands', 0);
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function unsupportedFieldProvider(): iterable
    {
        yield 'draft status' => [['name' => 'Draft Brand', 'status' => CentralBrandStatus::Draft->value], 'status'];
        yield 'active status' => [['name' => 'Active Brand', 'status' => CentralBrandStatus::Active->value], 'status'];
        yield 'archived status' => [['name' => 'Archived Brand', 'status' => CentralBrandStatus::Archived->value], 'status'];
        yield 'id' => [['name' => 'Brand With ID', 'id' => 99], 'id'];
    }
}
