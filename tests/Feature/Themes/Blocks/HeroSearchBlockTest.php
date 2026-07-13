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

class HeroSearchBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_hero_search_definition_is_registered_and_compatible(): void
    {
        $this->seed(BlockRegistrySeeder::class);
        $block = BlockDefinition::query()->where('code', 'hero_search')->firstOrFail();

        $this->assertTrue($block->isActive());
        $this->assertSame(['home'], $block->supported_page_types_json);
        $this->assertSame('string', $block->config_schema_json['title']);
        $this->assertSame('string', $block->config_schema_json['subtitle']);
        $this->assertSame('string', $block->config_schema_json['search_placeholder']);
        $this->assertSame('boolean', $block->config_schema_json['show_category_shortcuts']);
        $this->assertTrue(View::exists('components.blocks.hero-search'));

        $site = $this->siteWithThemeSupport('hero_search');
        app(BlockCompatibilityValidator::class)->validate($site, 'hero_search');
        $this->addToAssertionCount(1);
    }

    private function siteWithThemeSupport(string $capability): Site
    {
        $theme = Theme::factory()->create(['status' => ThemeStatus::Active]);
        ThemeManifestRecord::query()->create([
            'theme_id' => $theme->id,
            'manifest_json' => ['code' => $theme->code, 'name' => $theme->name, 'supports' => [$capability], 'layouts' => ['home' => 'home-clean']],
            'supports_json' => [$capability],
            'layouts_json' => ['home' => 'home-clean'],
        ]);

        return Site::factory()->create(['theme_id' => $theme->id]);
    }
}
