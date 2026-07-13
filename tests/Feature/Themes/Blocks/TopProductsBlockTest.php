<?php

namespace Tests\Feature\Themes\Blocks;

use App\Domains\Themes\Services\BlockCompatibilityValidator;
use App\Enums\ThemeStatus;
use App\Models\BlockDefinition;
use App\Models\Site;
use App\Models\Theme;
use App\Models\ThemeManifestRecord;
use Database\Seeders\BlockRegistrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class TopProductsBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BlockRegistrySeeder::class);
    }

    public function test_top_products_is_registered_with_explicit_schema(): void
    {
        $block = BlockDefinition::query()->where('code', 'top_products')->firstOrFail();

        $this->assertTrue($block->isActive());
        $this->assertSame(['home'], $block->supported_page_types_json);
        $this->assertSame('string', $block->config_schema_json['title']);
        $this->assertSame('integer', $block->config_schema_json['limit']);
        $this->assertSame('rating|popular|manual|latest', $block->config_schema_json['source']);
        $this->assertSame('integer', $block->config_schema_json['category_id']);
        $this->assertSame('grid|carousel|list', $block->config_schema_json['layout']);
        $this->assertTrue(View::exists('components.blocks.top-products'));
    }

    public function test_product_grid_theme_capability_allows_top_products(): void
    {
        $theme = Theme::factory()->create(['status' => ThemeStatus::Active]);
        ThemeManifestRecord::query()->create([
            'theme_id' => $theme->id,
            'manifest_json' => ['code' => $theme->code, 'name' => $theme->name, 'supports' => ['product_grid'], 'layouts' => ['home' => 'home-clean']],
            'supports_json' => ['product_grid'],
            'layouts_json' => ['home' => 'home-clean'],
        ]);
        $site = Site::factory()->create(['theme_id' => $theme->id]);

        app(BlockCompatibilityValidator::class)->validate($site, 'top_products');

        $this->addToAssertionCount(1);
    }
}
