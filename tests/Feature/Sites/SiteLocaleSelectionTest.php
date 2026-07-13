<?php

namespace Tests\Feature\Sites;

use App\Actions\Sites\CreateSiteAction;
use App\Enums\MarketStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Market;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SiteLocaleSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_locale_must_be_enabled(): void
    {
        $this->expectException(ValidationException::class);
        app(CreateSiteAction::class)->handle($this->data(['de-DE'], 'en-DE'));
    }

    public function test_enabled_locales_create_one_default_row_and_match_site(): void
    {
        $site = app(CreateSiteAction::class)->handle($this->data(['de-DE', 'en-DE'], 'de-DE'));

        $this->assertSame('de-DE', $site->default_locale);
        $this->assertDatabaseHas('site_locales', ['site_id' => $site->id, 'locale_code' => 'de-DE', 'is_default' => true]);
        $this->assertDatabaseHas('site_locales', ['site_id' => $site->id, 'locale_code' => 'en-DE', 'is_default' => false]);
    }

    /** @param list<string> $locales */
    private function data(array $locales, string $default): array
    {
        return ['market_id' => Market::factory()->create(['status' => MarketStatus::Active])->id, 'code' => fake()->unique()->slug(), 'name' => 'Locales', 'mode' => 'single_category', 'default_locale' => $default, 'locales' => $locales, 'categories' => [CentralCategory::factory()->create()->id], 'features' => []];
    }
}
