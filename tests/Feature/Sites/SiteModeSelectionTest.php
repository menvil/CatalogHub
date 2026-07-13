<?php

namespace Tests\Feature\Sites;

use App\Actions\Sites\CreateSiteAction;
use App\Enums\CentralCategoryStatus;
use App\Enums\MarketStatus;
use App\Enums\SiteMode;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use App\Models\Market;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SiteModeSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_category_mode_requires_exactly_one_category(): void
    {
        $data = $this->data(SiteMode::SingleCategory->value, CentralCategory::factory()->count(2)->create(['status' => CentralCategoryStatus::Active])->modelKeys());

        try {
            app(CreateSiteAction::class)->handle($data);

            $this->fail('A single-category site with multiple categories was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('categories', $exception->errors());
        }

        $this->assertDatabaseMissing('sites', ['code' => $data['code']]);
    }

    public function test_multi_category_mode_accepts_more_than_one_category(): void
    {
        $site = app(CreateSiteAction::class)->handle($this->data(SiteMode::MultiCategory->value, CentralCategory::factory()->count(2)->create(['status' => CentralCategoryStatus::Active])->modelKeys()));

        $this->assertSame(SiteMode::MultiCategory, $site->mode);
        $this->assertDatabaseCount('site_categories', 2);
    }

    public function test_multi_category_mode_rejects_empty_categories(): void
    {
        $data = $this->data(SiteMode::MultiCategory->value, []);

        try {
            app(CreateSiteAction::class)->handle($data);

            $this->fail('A multi-category site without categories was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('categories', $exception->errors());
        }

        $this->assertDatabaseMissing('sites', ['code' => $data['code']]);
    }

    /** @param list<int> $categories */
    private function data(string $mode, array $categories): array
    {
        $market = Market::factory()->create(['status' => MarketStatus::Active]);
        Locale::factory()->create(['code' => 'en-US']);

        return ['market_id' => $market->id, 'code' => fake()->unique()->slug(), 'name' => 'Site', 'mode' => $mode, 'default_locale' => 'en-US', 'locales' => ['en-US'], 'categories' => $categories, 'features' => []];
    }
}
