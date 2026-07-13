<?php

namespace Tests\Feature\Themes\Blocks;

use App\Domains\Themes\Services\BlockCompatibilityValidator;
use App\Enums\SiteMode;
use App\Enums\ThemeStatus;
use App\Models\BlockDefinition;
use App\Models\Site;
use App\Models\Theme;
use App\Models\ThemeManifestRecord;
use Database\Seeders\BlockRegistrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class PopularCategoriesBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BlockRegistrySeeder::class);
    }

    public function test_popular_categories_is_registered_for_home(): void
    {
        $block = BlockDefinition::query()->where('code', 'popular_categories')->firstOrFail();

        $this->assertTrue($block->isActive());
        $this->assertSame(['home'], $block->supported_page_types_json);
        $this->assertSame('string', $block->config_schema_json['title']);
        $this->assertSame('integer', $block->config_schema_json['limit']);
        $this->assertSame('grid|list|chips', $block->config_schema_json['layout']);
        $this->assertTrue(View::exists('components.blocks.popular-categories'));
    }

    public function test_popular_categories_is_compatible_with_single_and_multi_category_sites(): void
    {
        $theme = Theme::factory()->create(['status' => ThemeStatus::Active]);
        ThemeManifestRecord::query()->create([
            'theme_id' => $theme->id,
            'manifest_json' => ['code' => $theme->code, 'name' => $theme->name, 'supports' => ['popular_categories'], 'layouts' => ['home' => 'home-clean']],
            'supports_json' => ['popular_categories'],
            'layouts_json' => ['home' => 'home-clean'],
        ]);
        $single = Site::factory()->create(['theme_id' => $theme->id, 'mode' => SiteMode::SingleCategory]);
        $multi = Site::factory()->create(['theme_id' => $theme->id, 'mode' => SiteMode::MultiCategory]);
        $validator = app(BlockCompatibilityValidator::class);

        $validator->validate($single, 'popular_categories');
        $validator->validate($multi, 'popular_categories');

        $this->addToAssertionCount(2);
    }
}
