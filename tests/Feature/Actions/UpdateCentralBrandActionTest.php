<?php

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\UpdateCentralBrandAction;
use App\Data\CentralCatalog\CentralBrandInput;
use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\AssertsValidationErrors;
use Tests\Support\CountryReference;
use Tests\TestCase;

class UpdateCentralBrandActionTest extends TestCase
{
    use AssertsValidationErrors;
    use RefreshDatabase;

    public function test_updates_and_normalizes_canonical_fields_and_returns_the_persisted_model(): void
    {
        $country = CountryReference::get('CH');
        $brand = CentralBrand::factory()->draft()->create([
            'name' => 'Logitech',
            'slug' => 'logitech',
        ]);

        $result = app(UpdateCentralBrandAction::class)->handle(User::factory()->create(), $brand, new CentralBrandInput(
            name: '  Logitech   International  ',
            slug: ' Logitech_International ',
            hasWebsiteUrl: true,
            websiteUrl: ' https://www.logitech.com/en-us/ ',
            hasCountryId: true,
            countryId: $country->id,
        ));

        $this->assertTrue($result->is($brand));
        $this->assertSame('Logitech International', $result->name);
        $this->assertSame('logitech international', $result->normalized_name);
        $this->assertSame(hash('sha256', 'logitech international'), $result->normalized_name_hash);
        $this->assertSame('logitech-international', $result->slug);
        $this->assertSame('https://www.logitech.com/en-us/', $result->website_url);
        $this->assertSame($country->id, $result->country_id);
        $this->assertSame('CH', $result->country?->alpha2);
        $this->assertSame(CentralBrandStatus::Draft, $result->status);
        $this->assertSame('Logitech International', $brand->fresh()->name);
    }

    public function test_preserves_the_stable_slug_when_name_changes_and_slug_is_omitted(): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Samsung', 'slug' => 'samsung']);

        $result = app(UpdateCentralBrandAction::class)->handle(User::factory()->create(),
            $brand,
            new CentralBrandInput(name: 'Samsung Electronics'),
        );

        $this->assertSame('Samsung Electronics', $result->name);
        $this->assertSame('samsung', $result->slug);
    }

    public function test_blank_or_null_slug_preserves_the_existing_slug(): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Samsung', 'slug' => 'samsung']);

        $blankResult = app(UpdateCentralBrandAction::class)->handle(User::factory()->create(),
            $brand,
            new CentralBrandInput(name: 'Samsung', slug: '   '),
        );
        $this->assertSame('samsung', $blankResult->slug);

        $nullResult = app(UpdateCentralBrandAction::class)->handle(User::factory()->create(),
            $brand,
            new CentralBrandInput(name: 'Samsung', slug: null),
        );
        $this->assertSame('samsung', $nullResult->slug);
    }

    public function test_clears_nullable_metadata_with_blank_or_null_input(): void
    {
        $blank = CentralBrand::factory()->create([
            'name' => 'Brand One',
            'website_url' => 'https://example.com',
            'country_id' => CountryReference::id('US'),
        ]);
        $null = CentralBrand::factory()->create([
            'name' => 'Brand Two',
            'website_url' => 'https://example.org',
            'country_id' => CountryReference::id('DE'),
        ]);

        $blankResult = app(UpdateCentralBrandAction::class)->handle(User::factory()->create(), $blank, new CentralBrandInput(
            name: 'Brand One',
            hasWebsiteUrl: true,
            websiteUrl: '   ',
            hasCountryId: true,
            countryId: null,
        ));
        $nullResult = app(UpdateCentralBrandAction::class)->handle(User::factory()->create(), $null, new CentralBrandInput(
            name: 'Brand Two',
            hasWebsiteUrl: true,
            websiteUrl: null,
            hasCountryId: true,
            countryId: null,
        ));

        $this->assertNull($blankResult->website_url);
        $this->assertNull($blankResult->country_id);
        $this->assertNull($nullResult->website_url);
        $this->assertNull($nullResult->country_id);
    }

    public function test_rejects_a_slug_owned_by_another_brand(): void
    {
        CentralBrand::factory()->create(['slug' => 'samsung']);
        $brand = CentralBrand::factory()->create(['name' => 'LG', 'slug' => 'lg']);

        $this->assertValidationError('slug', fn () => app(UpdateCentralBrandAction::class)->handle(User::factory()->create(),
            $brand,
            new CentralBrandInput(name: 'LG', slug: 'samsung'),
        ));

        $this->assertSame('lg', $brand->fresh()->slug);
    }

    public function test_allows_the_current_brands_own_slug(): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Samsung', 'slug' => 'samsung']);

        $result = app(UpdateCentralBrandAction::class)->handle(User::factory()->create(),
            $brand,
            new CentralBrandInput(name: 'Samsung', slug: ' Samsung '),
        );

        $this->assertSame('samsung', $result->slug);
    }

    public function test_rejects_an_invalid_explicit_slug_as_a_field_validation_error(): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Samsung', 'slug' => 'samsung']);

        $this->assertValidationError('slug', fn () => app(UpdateCentralBrandAction::class)->handle(User::factory()->create(),
            $brand,
            new CentralBrandInput(name: 'Samsung', slug: 'Samsung!!!'),
        ));

        $this->assertSame('samsung', $brand->fresh()->slug);
    }

    #[DataProvider('duplicateNameProvider')]
    public function test_rejects_a_normalized_name_owned_by_another_brand(string $existing, string $duplicate): void
    {
        CentralBrand::factory()->create(['name' => $existing, 'slug' => 'existing-brand']);
        $brand = CentralBrand::factory()->create(['name' => 'LG', 'slug' => 'lg']);

        $this->assertValidationError('name', fn () => app(UpdateCentralBrandAction::class)->handle(User::factory()->create(),
            $brand,
            new CentralBrandInput(name: $duplicate),
        ));

        $this->assertSame('LG', $brand->fresh()->name);
    }

    /** @return iterable<string, array{string, string}> */
    public static function duplicateNameProvider(): iterable
    {
        yield 'lowercase' => ['Samsung', 'samsung'];
        yield 'uppercase' => ['Samsung', 'SAMSUNG'];
        yield 'whitespace' => ['Samsung', ' Samsung '];
        yield 'Unicode case fold' => ['ÉLECTRO', 'électro'];
        yield 'canonically equivalent Unicode' => ['ÉLECTRO', "e\u{0301}lectro"];
    }

    public function test_allows_the_current_brands_own_normalized_name_and_similar_names(): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Samsung', 'slug' => 'samsung']);
        CentralBrand::factory()->create(['name' => 'Samsung Electronics', 'slug' => 'samsung-electronics']);

        app(UpdateCentralBrandAction::class)->handle(User::factory()->create(), $brand, new CentralBrandInput(name: ' Samsung '));
        $result = app(UpdateCentralBrandAction::class)->handle(User::factory()->create(),
            $brand,
            new CentralBrandInput(name: 'Samsung Display'),
        );

        $this->assertSame('Samsung Display', $result->name);
        $this->assertSame('samsung', $result->slug);
    }

    public function test_allows_the_current_brands_own_unicode_normalized_name(): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'ÉLECTRO', 'slug' => 'electro']);

        $result = app(UpdateCentralBrandAction::class)->handle(User::factory()->create(),
            $brand,
            new CentralBrandInput(name: 'électro'),
        );

        $this->assertSame('électro', $result->name);
        $this->assertSame($brand->getKey(), $result->getKey());
    }

    public function test_unicode_duplicate_guard_remains_accent_sensitive(): void
    {
        CentralBrand::factory()->create(['name' => 'ÉLECTRO', 'slug' => 'electro-accented']);
        $brand = CentralBrand::factory()->create(['name' => 'Other', 'slug' => 'other']);

        $result = app(UpdateCentralBrandAction::class)->handle(User::factory()->create(),
            $brand,
            new CentralBrandInput(name: 'Electro'),
        );

        $this->assertSame('Electro', $result->name);
    }

    #[DataProvider('invalidWebsiteProvider')]
    public function test_rejects_invalid_website_urls(string $websiteUrl): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Sony']);

        $this->assertValidationError('website_url', fn () => app(UpdateCentralBrandAction::class)->handle(User::factory()->create(),
            $brand,
            new CentralBrandInput(name: 'Sony', hasWebsiteUrl: true, websiteUrl: $websiteUrl),
        ));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidWebsiteProvider(): iterable
    {
        yield 'missing scheme' => ['sony.com'];
        yield 'ftp' => ['ftp://sony.com'];
        yield 'javascript' => ['javascript:alert(1)'];
    }

    public function test_rejects_inactive_and_unknown_country_assignments(): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Sony']);
        $inactive = CountryReference::get('JP');
        $inactive->update(['is_active' => false]);

        foreach ([$inactive->id, PHP_INT_MAX] as $countryId) {
            $this->assertValidationError('country_id', fn () => app(UpdateCentralBrandAction::class)->handle(User::factory()->create(),
                $brand,
                new CentralBrandInput(name: 'Sony', hasCountryId: true, countryId: $countryId),
            ));
        }
    }

    public function test_historical_inactive_country_is_preserved_when_omitted_or_reselected_and_can_change_to_active(): void
    {
        $inactive = CountryReference::get('KR');
        $inactive->update(['is_active' => false]);
        $active = CountryReference::get('JP');
        $brand = CentralBrand::factory()->create([
            'name' => 'Samsung',
            'website_url' => 'https://old.example',
            'country_id' => $inactive->id,
        ]);
        $action = app(UpdateCentralBrandAction::class);
        $actor = User::factory()->create();

        $omitted = $action->handle($actor, $brand, new CentralBrandInput(
            name: 'Samsung',
            hasWebsiteUrl: true,
            websiteUrl: 'https://new.example',
        ));
        $this->assertSame($inactive->id, $omitted->country_id);

        $reselected = $action->handle($actor, $omitted, new CentralBrandInput(
            name: 'Samsung',
            hasCountryId: true,
            countryId: $inactive->id,
        ));
        $this->assertSame($inactive->id, $reselected->country_id);

        $changed = $action->handle($actor, $reselected, new CentralBrandInput(
            name: 'Samsung',
            hasCountryId: true,
            countryId: $active->id,
        ));
        $this->assertSame($active->id, $changed->country_id);
    }

    public function test_validation_finishes_before_any_field_is_persisted(): void
    {
        $brand = CentralBrand::factory()->create([
            'name' => 'Samsung',
            'website_url' => 'https://www.samsung.com',
            'country_id' => CountryReference::id('KR'),
        ]);

        $this->assertValidationError('website_url', fn () => app(UpdateCentralBrandAction::class)->handle(User::factory()->create(),
            $brand,
            new CentralBrandInput(
                name: 'Samsung Electronics',
                hasWebsiteUrl: true,
                websiteUrl: 'not-a-url',
                hasCountryId: true,
                countryId: CountryReference::id('US'),
            ),
        ));

        $brand->refresh();
        $this->assertSame('Samsung', $brand->name);
        $this->assertSame('https://www.samsung.com', $brand->website_url);
        $this->assertSame('KR', $brand->country()->first()?->alpha2);
    }

    #[DataProvider('statusProvider')]
    public function test_can_edit_brands_in_every_lifecycle_state(CentralBrandStatus $status): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Sony', 'status' => $status]);

        $result = app(UpdateCentralBrandAction::class)->handle(User::factory()->create(),
            $brand,
            new CentralBrandInput(name: 'Sony Corporation'),
        );

        $this->assertSame('Sony Corporation', $result->name);
        $this->assertSame($status, $result->status);
    }

    /** @return iterable<string, array{CentralBrandStatus}> */
    public static function statusProvider(): iterable
    {
        yield 'draft' => [CentralBrandStatus::Draft];
        yield 'active' => [CentralBrandStatus::Active];
        yield 'archived' => [CentralBrandStatus::Archived];
    }
}
