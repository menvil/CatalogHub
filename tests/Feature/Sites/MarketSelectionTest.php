<?php

namespace Tests\Feature\Sites;

use App\Actions\Sites\CreateSiteAction;
use App\Enums\MarketStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Market;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MarketSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_market_cannot_be_used_to_create_site(): void
    {
        $market = Market::factory()->create(['status' => MarketStatus::Archived]);

        $this->expectException(ValidationException::class);
        app(CreateSiteAction::class)->handle([
            'market_id' => $market->id, 'code' => 'archived-market', 'name' => 'Archived', 'mode' => 'single_category',
            'default_locale' => 'en-US', 'locales' => ['en-US'], 'categories' => [CentralCategory::factory()->create()->id], 'features' => [],
        ]);
    }

    public function test_active_market_is_saved_on_site(): void
    {
        $market = Market::factory()->create(['status' => MarketStatus::Active]);
        $site = app(CreateSiteAction::class)->handle([
            'market_id' => $market->id, 'code' => 'active-market', 'name' => 'Active', 'mode' => 'single_category',
            'default_locale' => 'en-US', 'locales' => ['en-US'], 'categories' => [CentralCategory::factory()->create()->id], 'features' => [],
        ]);

        $this->assertTrue($site->market->is($market));
    }
}
