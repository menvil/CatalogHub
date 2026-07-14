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

class BuyingGuidesBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BlockRegistrySeeder::class);
    }

    public function test_buying_guides_is_registered_for_home_and_category_pages(): void
    {
        $block = BlockDefinition::query()->where('code', 'buying_guides')->firstOrFail();

        $this->assertTrue($block->isActive());
        $this->assertSame(['home', 'category'], $block->supported_page_types_json);
        $this->assertSame(['guides'], $block->required_features_json);
        $this->assertSame('buying_guide|how_to_guide|article', $block->config_schema_json['content_type']);
        $this->assertSame('cards|list', $block->config_schema_json['layout']);
        $this->assertTrue(View::exists('components.blocks.buying-guides'));
    }

    public function test_buying_guides_requires_guides_or_blog_feature(): void
    {
        $site = $this->siteWithThemeSupport();
        $validator = app(BlockCompatibilityValidator::class);

        try {
            $validator->validate($site, 'buying_guides');
            $this->fail('Disabled content features should block buying_guides.');
        } catch (CannotUseBlockException $exception) {
            $this->assertStringContainsString('guides, blog', $exception->getMessage());
        }

        SiteFeature::query()->create(['site_id' => $site->id, 'feature_key' => 'blog', 'is_enabled' => true]);
        $validator->validate($site, 'buying_guides', 'home');
        $validator->validate($site, 'buying_guides', 'category');
        $this->addToAssertionCount(2);
    }

    private function siteWithThemeSupport(): Site
    {
        $theme = Theme::factory()->create(['status' => ThemeStatus::Active]);
        ThemeManifestRecord::query()->create([
            'theme_id' => $theme->id,
            'manifest_json' => ['code' => $theme->code, 'name' => $theme->name, 'supports' => ['buying_guides'], 'layouts' => ['home' => 'home-clean']],
            'supports_json' => ['buying_guides'],
            'layouts_json' => ['home' => 'home-clean'],
        ]);

        return Site::factory()->create(['theme_id' => $theme->id]);
    }
}
