<?php

namespace Tests\Feature\Themes\Blocks;

use App\Domains\Themes\Services\BlockCompatibilityValidator;
use App\Enums\ThemeStatus;
use App\Exceptions\Themes\CannotUseBlockException;
use App\Models\BlockDefinition;
use App\Models\Site;
use App\Models\SiteFeature;
use App\Models\Theme;
use App\Models\ThemeManifestRecord;
use Database\Seeders\BlockRegistrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class PriceBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BlockRegistrySeeder::class);
    }

    public function test_price_block_is_registered_for_supported_page_types(): void
    {
        $block = BlockDefinition::query()->where('code', 'price_block')->firstOrFail();

        $this->assertTrue($block->isActive());
        $this->assertSame(['home', 'category', 'product'], $block->supported_page_types_json);
        $this->assertSame(['price_comparison'], $block->required_features_json);
        $this->assertSame('best_offer|offers_table|price_widget|coverage', $block->config_schema_json['mode']);
        $this->assertTrue(View::exists('components.blocks.price-block'));
    }

    public function test_price_block_requires_any_supported_price_feature(): void
    {
        [$theme, $siteWithoutPriceFeature] = $this->themeAndSite();
        $validator = app(BlockCompatibilityValidator::class);

        try {
            $validator->validate($siteWithoutPriceFeature, 'price_block');
            $this->fail('A price feature should be required.');
        } catch (CannotUseBlockException $exception) {
            $this->assertStringContainsString('price_comparison, local_offers, external_price_widget', $exception->getMessage());
        }

        foreach (['price_comparison', 'local_offers', 'external_price_widget'] as $feature) {
            $site = Site::factory()->create(['theme_id' => $theme->id]);
            SiteFeature::query()->create(['site_id' => $site->id, 'feature_key' => $feature, 'is_enabled' => true]);

            foreach (['home', 'category', 'product'] as $pageType) {
                $validator->validate($site, 'price_block', $pageType);
            }
        }

        $this->addToAssertionCount(9);
    }

    /** @return array{Theme, Site} */
    private function themeAndSite(): array
    {
        $theme = Theme::factory()->create(['status' => ThemeStatus::Active]);
        ThemeManifestRecord::query()->create([
            'theme_id' => $theme->id,
            'manifest_json' => ['code' => $theme->code, 'name' => $theme->name, 'supports' => ['price_block'], 'layouts' => ['home' => 'home-clean']],
            'supports_json' => ['price_block'],
            'layouts_json' => ['home' => 'home-clean'],
        ]);

        return [$theme, Site::factory()->create(['theme_id' => $theme->id])];
    }
}
