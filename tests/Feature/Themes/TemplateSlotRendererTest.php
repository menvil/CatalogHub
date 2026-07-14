<?php

namespace Tests\Feature\Themes;

use App\Domains\Themes\Services\TemplateSlotRenderer;
use App\Enums\BlockStatus;
use App\Enums\ThemeStatus;
use App\Models\BlockDefinition;
use App\Models\LayoutTemplate;
use App\Models\Site;
use App\Models\SiteHomeBlock;
use App\Models\Theme;
use App\Models\ThemeManifestRecord;
use Database\Seeders\BlockRegistrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TemplateSlotRendererTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BlockRegistrySeeder::class);
    }

    public function test_renderer_resolves_manifest_layout_and_enabled_blocks_in_position_order(): void
    {
        [$site, $layout] = $this->configuredSite(['top_products', 'hero_search', 'popular_categories']);
        SiteHomeBlock::factory()->create(['site_id' => $site->id, 'block_code' => 'hero_search', 'position' => 2, 'config_json' => ['title' => 'Find products']]);
        SiteHomeBlock::factory()->create(['site_id' => $site->id, 'block_code' => 'top_products', 'position' => 1, 'config_json' => ['limit' => 8]]);
        SiteHomeBlock::factory()->create(['site_id' => $site->id, 'block_code' => 'popular_categories', 'position' => 3, 'enabled' => false]);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $rendered = app(TemplateSlotRenderer::class)->renderHome($site);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertTrue($rendered->layout?->is($layout));
        $this->assertSame(['top_products', 'hero_search'], $rendered->blocks->pluck('code')->all());
        $this->assertSame(['blocks.top-products', 'blocks.hero-search'], $rendered->blocks->pluck('viewComponent')->all());
        $this->assertSame(8, $rendered->blocks->first()->config['limit']);
        $this->assertSame([], app(TemplateSlotRenderer::class)->blocksFor($site, 'category')->all());
        $this->assertLessThanOrEqual(8, count($queries));
        $this->assertFalse(collect($queries)->contains(fn (array $query): bool => str_contains($query['query'], 'central_products') || str_contains($query['query'], 'projection')));
    }

    public function test_renderer_returns_null_when_manifest_layout_is_not_active(): void
    {
        [$site, $layout] = $this->configuredSite(['hero_search']);
        $layout->update(['status' => 'archived']);

        $this->assertNull(app(TemplateSlotRenderer::class)->resolveLayout($site, 'home'));
        $this->assertNull(app(TemplateSlotRenderer::class)->resolveLayout($site, 'search'));
    }

    public function test_invalid_manifest_is_logged_before_layout_resolution_falls_back_to_null(): void
    {
        [$site] = $this->configuredSite(['hero_search']);
        $site->theme->manifest()->update([
            'manifest_json' => ['code' => $site->theme->code, 'supports' => ['hero_search'], 'layouts' => ['home' => 'home-clean']],
        ]);
        $site->unsetRelation('theme');
        $this->assertNull(app(TemplateSlotRenderer::class)->resolveLayout($site, 'home'));
    }

    public function test_renderer_logs_and_skips_incompatible_block_while_rendering_compatible_blocks(): void
    {
        [$site] = $this->configuredSite(['hero_search', 'top_products']);
        SiteHomeBlock::factory()->create(['site_id' => $site->id, 'block_code' => 'hero_search', 'position' => 1]);
        SiteHomeBlock::factory()->create(['site_id' => $site->id, 'block_code' => 'top_products', 'position' => 2]);
        BlockDefinition::query()->where('code', 'hero_search')->update(['status' => BlockStatus::Archived]);
        $rendered = app(TemplateSlotRenderer::class)->renderHome($site);

        $this->assertSame(['top_products'], $rendered->blocks->pluck('code')->all());
    }

    /**
     * @param  list<string>  $supports
     * @return array{Site, LayoutTemplate}
     */
    private function configuredSite(array $supports): array
    {
        $theme = Theme::factory()->create(['status' => ThemeStatus::Active]);
        ThemeManifestRecord::query()->create([
            'theme_id' => $theme->id,
            'manifest_json' => ['code' => $theme->code, 'name' => $theme->name, 'supports' => $supports, 'layouts' => ['home' => 'home-clean']],
            'supports_json' => $supports,
            'layouts_json' => ['home' => 'home-clean'],
        ]);
        $layout = LayoutTemplate::query()->create([
            'theme_id' => $theme->id,
            'page_type' => 'home',
            'code' => 'home-clean',
            'name' => 'Home Clean',
            'view_path' => 'themes.catalog-clean.home',
            'slots_json' => ['main'],
            'status' => 'active',
        ]);

        return [Site::factory()->create(['theme_id' => $theme->id]), $layout];
    }
}
