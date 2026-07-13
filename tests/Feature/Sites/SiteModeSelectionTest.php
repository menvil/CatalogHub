<?php

namespace Tests\Feature\Sites;

use App\Actions\Sites\CreateSiteAction;
use App\Enums\CentralCategoryStatus;
use App\Enums\MarketStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Market;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SiteModeSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_category_mode_requires_exactly_one_category(): void
    {
        $this->expectException(ValidationException::class);

        app(CreateSiteAction::class)->handle($this->data('single_category', CentralCategory::factory()->count(2)->create(['status' => CentralCategoryStatus::Active])->modelKeys()));
    }

    public function test_multi_category_mode_accepts_more_than_one_category(): void
    {
        $site = app(CreateSiteAction::class)->handle($this->data('multi_category', CentralCategory::factory()->count(2)->create(['status' => CentralCategoryStatus::Active])->modelKeys()));

        $this->assertSame('multi_category', $site->mode->value);
        $this->assertDatabaseCount('site_categories', 2);
    }

    /** @param list<int> $categories */
    private function data(string $mode, array $categories): array
    {
        $market = Market::factory()->create(['status' => MarketStatus::Active]);

        return ['market_id' => $market->id, 'code' => fake()->unique()->slug(), 'name' => 'Site', 'mode' => $mode, 'default_locale' => 'en-US', 'locales' => ['en-US'], 'categories' => $categories, 'features' => []];
    }
}
