<?php

namespace Tests\Feature\Models;

use App\Enums\ThemeStatus;
use App\Models\LayoutTemplate;
use App\Models\Theme;
use App\Models\ThemeManifestRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_theme_with_expected_casts_and_active_scope(): void
    {
        $active = Theme::factory()->create([
            'status' => ThemeStatus::Active,
            'is_system' => true,
            'config_json' => ['color_scheme' => 'clean'],
        ]);
        Theme::factory()->create(['status' => ThemeStatus::Archived]);

        $this->assertTrue($active->isActive());
        $this->assertSame(ThemeStatus::Active, $active->status);
        $this->assertTrue($active->is_system);
        $this->assertSame(['color_scheme' => 'clean'], $active->config_json);
        $this->assertSame([$active->id], Theme::query()->active()->pluck('id')->all());
    }

    public function test_theme_has_manifest_and_layout_template_relations(): void
    {
        $theme = Theme::factory()->create();
        $manifest = ThemeManifestRecord::query()->create([
            'theme_id' => $theme->id,
            'manifest_json' => ['code' => $theme->code],
            'supports_json' => [],
            'layouts_json' => ['home' => 'home-clean'],
        ]);
        $layout = LayoutTemplate::query()->create([
            'theme_id' => $theme->id,
            'page_type' => 'home',
            'code' => 'home-clean',
            'name' => 'Home Clean',
            'view_path' => 'themes.catalog-clean.home',
            'status' => 'active',
        ]);

        $this->assertTrue($theme->manifest->is($manifest));
        $this->assertTrue($theme->layoutTemplates->contains($layout));
        $this->assertTrue($manifest->theme->is($theme));
        $this->assertTrue($layout->theme->is($theme));
    }
}
