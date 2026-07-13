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
}
