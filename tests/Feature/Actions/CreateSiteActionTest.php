<?php

namespace Tests\Feature\Actions;

use App\Actions\Sites\CreateSiteAction;
use App\Enums\CentralCategoryStatus;
use App\Enums\MarketStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Market;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSiteActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_site_with_locales_categories_and_features(): void
    {
        $market = Market::factory()->create(['status' => MarketStatus::Active]);
        $category = CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active]);

        $site = app(CreateSiteAction::class)->handle([
            'market_id' => $market->id, 'code' => 'de-monitors', 'name' => 'DE Monitors', 'mode' => 'single_category', 'default_locale' => 'de-DE',
            'locales' => ['de-DE'], 'categories' => [$category->id], 'features' => ['comparison' => true],
        ]);

        $this->assertDatabaseHas('sites', ['id' => $site->id, 'market_id' => $market->id]);
        $this->assertDatabaseHas('site_locales', ['site_id' => $site->id, 'locale_code' => 'de-DE', 'is_default' => true]);
        $this->assertDatabaseHas('site_categories', ['site_id' => $site->id, 'central_category_id' => $category->id]);
        $this->assertDatabaseHas('site_features', ['site_id' => $site->id, 'feature_key' => 'comparison', 'is_enabled' => true]);
    }
}
