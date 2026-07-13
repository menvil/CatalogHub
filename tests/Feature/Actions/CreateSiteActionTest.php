<?php

namespace Tests\Feature\Actions;

use App\Actions\Sites\CreateSiteAction;
use App\Enums\CentralCategoryStatus;
use App\Enums\MarketStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use App\Models\Market;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateSiteActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_site_with_locales_categories_and_features(): void
    {
        $market = Market::factory()->create(['status' => MarketStatus::Active]);
        $category = CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active]);
        Locale::factory()->create(['code' => 'de-DE']);

        $site = app(CreateSiteAction::class)->handle([
            'market_id' => $market->id, 'code' => 'de-monitors', 'name' => 'DE Monitors', 'mode' => 'single_category', 'default_locale' => 'de-DE',
            'locales' => ['de-DE'], 'categories' => [$category->id], 'features' => ['comparison' => true],
        ]);

        $this->assertDatabaseHas('sites', ['id' => $site->id, 'market_id' => $market->id]);
        $this->assertDatabaseHas('site_locales', ['site_id' => $site->id, 'locale_code' => 'de-DE', 'is_default' => true]);
        $this->assertDatabaseHas('site_categories', ['site_id' => $site->id, 'central_category_id' => $category->id]);
        $this->assertDatabaseHas('site_features', ['site_id' => $site->id, 'feature_key' => 'comparison', 'is_enabled' => true]);
    }

    public function test_features_are_optional(): void
    {
        $market = Market::factory()->create(['status' => MarketStatus::Active]);
        $category = CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active]);
        Locale::factory()->create(['code' => 'en-US']);

        $site = app(CreateSiteAction::class)->handle([
            'market_id' => $market->id,
            'code' => 'site-without-features',
            'name' => 'Site without features',
            'mode' => 'single_category',
            'default_locale' => 'en-US',
            'locales' => ['en-US'],
            'categories' => [$category->id],
        ]);

        $this->assertTrue($site->exists);
        $this->assertDatabaseMissing('site_features', ['site_id' => $site->id]);
    }

    public function test_inactive_locale_cannot_be_enabled(): void
    {
        $market = Market::factory()->create(['status' => MarketStatus::Active]);
        $category = CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active]);
        Locale::factory()->create(['code' => 'de-DE', 'is_active' => false]);

        try {
            app(CreateSiteAction::class)->handle([
                'market_id' => $market->id,
                'code' => 'inactive-locale',
                'name' => 'Inactive locale',
                'mode' => 'single_category',
                'default_locale' => 'de-DE',
                'locales' => ['de-DE'],
                'categories' => [$category->id],
            ]);

            $this->fail('An inactive locale was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('locales', $exception->errors());
        }

        $this->assertDatabaseMissing('sites', ['code' => 'inactive-locale']);
    }

    public function test_unsupported_feature_key_is_rejected(): void
    {
        $market = Market::factory()->create(['status' => MarketStatus::Active]);
        $category = CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active]);
        Locale::factory()->create(['code' => 'en-US']);

        try {
            app(CreateSiteAction::class)->handle([
                'market_id' => $market->id,
                'code' => 'unsupported-feature',
                'name' => 'Unsupported feature',
                'mode' => 'single_category',
                'default_locale' => 'en-US',
                'locales' => ['en-US'],
                'categories' => [$category->id],
                'features' => ['unsupported' => true],
            ]);

            $this->fail('An unsupported feature key was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('features', $exception->errors());
        }

        $this->assertDatabaseMissing('sites', ['code' => 'unsupported-feature']);
    }

    public function test_feature_values_must_be_strict_booleans(): void
    {
        $market = Market::factory()->create(['status' => MarketStatus::Active]);
        $category = CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active]);
        Locale::factory()->create(['code' => 'en-US']);

        try {
            app(CreateSiteAction::class)->handle([
                'market_id' => $market->id,
                'code' => 'malformed-feature',
                'name' => 'Malformed feature',
                'mode' => 'single_category',
                'default_locale' => 'en-US',
                'locales' => ['en-US'],
                'categories' => [$category->id],
                'features' => ['comparison' => 'false'],
            ]);

            $this->fail('A non-boolean feature value was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('features.comparison', $exception->errors());
        }

        $this->assertDatabaseMissing('sites', ['code' => 'malformed-feature']);
    }

    public function test_empty_locales_are_rejected(): void
    {
        $market = Market::factory()->create(['status' => MarketStatus::Active]);
        Locale::factory()->create(['code' => 'en-US']);

        try {
            app(CreateSiteAction::class)->handle([
                'market_id' => $market->id,
                'code' => 'empty-locales',
                'name' => 'Empty locales',
                'mode' => 'single_category',
                'default_locale' => 'en-US',
                'locales' => [],
                'categories' => [CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active])->id],
            ]);

            $this->fail('A site without locales was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('locales', $exception->errors());
        }

        $this->assertDatabaseMissing('sites', ['code' => 'empty-locales']);
    }

    public function test_locales_must_be_an_array_before_normalization(): void
    {
        $market = Market::factory()->create(['status' => MarketStatus::Active]);

        try {
            app(CreateSiteAction::class)->handle([
                'market_id' => $market->id,
                'locales' => 'en-US',
            ]);

            $this->fail('A scalar locales payload was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('locales', $exception->errors());
        }

        $this->assertDatabaseCount('sites', 0);
    }

    public function test_locale_members_must_be_strings_before_normalization(): void
    {
        $market = Market::factory()->create(['status' => MarketStatus::Active]);

        try {
            app(CreateSiteAction::class)->handle([
                'market_id' => $market->id,
                'locales' => [['en-US']],
            ]);

            $this->fail('A nested locale payload was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('locales.0', $exception->errors());
        }

        $this->assertDatabaseCount('sites', 0);
    }

    public function test_default_locale_must_be_in_enabled_locales(): void
    {
        $market = Market::factory()->create(['status' => MarketStatus::Active]);
        Locale::factory()->create(['code' => 'en-US']);

        try {
            app(CreateSiteAction::class)->handle([
                'market_id' => $market->id,
                'code' => 'disabled-default-locale',
                'name' => 'Disabled default locale',
                'mode' => 'single_category',
                'default_locale' => 'de-DE',
                'locales' => ['en-US'],
                'categories' => [CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active])->id],
            ]);

            $this->fail('A default locale outside the enabled locales was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('default_locale', $exception->errors());
        }

        $this->assertDatabaseMissing('sites', ['code' => 'disabled-default-locale']);
    }

    public function test_missing_and_unknown_modes_are_rejected(): void
    {
        $market = Market::factory()->create(['status' => MarketStatus::Active]);
        $category = CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active]);
        Locale::factory()->create(['code' => 'en-US']);

        foreach ([null, 'unsupported'] as $mode) {
            $data = [
                'market_id' => $market->id,
                'code' => 'invalid-mode-'.($mode ?? 'missing'),
                'name' => 'Invalid mode',
                'default_locale' => 'en-US',
                'locales' => ['en-US'],
                'categories' => [$category->id],
            ];

            if ($mode !== null) {
                $data['mode'] = $mode;
            }

            try {
                app(CreateSiteAction::class)->handle($data);
                $this->fail('A missing or unsupported site mode was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('mode', $exception->errors());
            }
        }

        $this->assertDatabaseCount('sites', 0);
    }

    public function test_category_ids_are_validated_as_integers_before_normalization(): void
    {
        $market = Market::factory()->create(['status' => MarketStatus::Active]);
        $category = CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active]);
        Locale::factory()->create(['code' => 'en-US']);

        try {
            app(CreateSiteAction::class)->handle([
                'market_id' => $market->id,
                'code' => 'malformed-category',
                'name' => 'Malformed category',
                'mode' => 'single_category',
                'default_locale' => 'en-US',
                'locales' => ['en-US'],
                'categories' => [$category->id + 0.5],
            ]);

            $this->fail('A malformed category id was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('categories.0', $exception->errors());
        }

        $this->assertDatabaseCount('sites', 0);
    }

    public function test_unsupported_site_status_is_rejected(): void
    {
        $market = Market::factory()->create(['status' => MarketStatus::Active]);
        $category = CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active]);
        Locale::factory()->create(['code' => 'en-US']);

        try {
            app(CreateSiteAction::class)->handle([
                'market_id' => $market->id,
                'code' => 'invalid-status',
                'name' => 'Invalid status',
                'mode' => 'single_category',
                'status' => 'unsupported',
                'default_locale' => 'en-US',
                'locales' => ['en-US'],
                'categories' => [$category->id],
            ]);

            $this->fail('An unsupported site status was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertDatabaseCount('sites', 0);
    }
}
