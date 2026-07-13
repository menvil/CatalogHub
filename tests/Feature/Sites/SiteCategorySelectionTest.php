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

class SiteCategorySelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_category_cannot_be_enabled(): void
    {
        $category = CentralCategory::factory()->create(['status' => CentralCategoryStatus::Archived]);
        $this->expectException(ValidationException::class);
        app(CreateSiteAction::class)->handle($this->data([$category->id]));
    }

    public function test_enabled_categories_are_deduplicated_and_ordered(): void
    {
        $categories = CentralCategory::factory()->count(2)->create(['status' => CentralCategoryStatus::Active]);
        $site = app(CreateSiteAction::class)->handle($this->data([$categories[1]->id, $categories[0]->id, $categories[1]->id]));

        $this->assertDatabaseHas('site_categories', ['site_id' => $site->id, 'central_category_id' => $categories[1]->id, 'position' => 0]);
        $this->assertDatabaseHas('site_categories', ['site_id' => $site->id, 'central_category_id' => $categories[0]->id, 'position' => 1]);
        $this->assertDatabaseCount('site_categories', 2);
    }

    /** @param list<int> $categories */
    private function data(array $categories): array
    {
        return ['market_id' => Market::factory()->create(['status' => MarketStatus::Active])->id, 'code' => fake()->unique()->slug(), 'name' => 'Categories', 'mode' => 'multi_category', 'default_locale' => 'en-US', 'locales' => ['en-US'], 'categories' => $categories, 'features' => []];
    }
}
