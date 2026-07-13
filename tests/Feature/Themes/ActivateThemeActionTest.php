<?php

namespace Tests\Feature\Themes;

use App\Domains\Themes\Actions\ActivateThemeAction;
use App\Enums\BlockStatus;
use App\Enums\ThemeStatus;
use App\Exceptions\Themes\CannotActivateThemeException;
use App\Models\BlockDefinition;
use App\Models\Site;
use App\Models\SiteFeature;
use App\Models\SiteHomeBlock;
use App\Models\Theme;
use App\Models\ThemeManifestRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivateThemeActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_compatible_active_theme_can_be_activated(): void
    {
        $site = Site::factory()->create(['settings_json' => ['brand' => 'CatalogHub']]);
        SiteFeature::query()->create(['site_id' => $site->id, 'feature_key' => 'reviews', 'is_enabled' => true]);
        $theme = $this->theme(ThemeStatus::Active, ['latest_reviews']);

        app(ActivateThemeAction::class)->handle($site, $theme);

        $site->refresh();
        $this->assertTrue($site->theme->is($theme));
        $this->assertSame(['brand' => 'CatalogHub'], $site->settings_json);
    }

    public function test_incompatible_theme_cannot_be_activated(): void
    {
        $site = Site::factory()->create();
        SiteFeature::query()->create(['site_id' => $site->id, 'feature_key' => 'reviews', 'is_enabled' => true]);
        $theme = $this->theme(ThemeStatus::Active, []);

        $this->expectException(CannotActivateThemeException::class);
        $this->expectExceptionMessage('reviews');

        app(ActivateThemeAction::class)->handle($site, $theme);
    }

    public function test_inactive_theme_cannot_be_activated(): void
    {
        $site = Site::factory()->create();
        $theme = $this->theme(ThemeStatus::Archived, []);

        $this->expectException(CannotActivateThemeException::class);
        $this->expectExceptionMessage('not active');

        app(ActivateThemeAction::class)->handle($site, $theme);
    }

    public function test_theme_missing_an_enabled_homepage_block_capability_cannot_be_activated(): void
    {
        $current = $this->theme(ThemeStatus::Active, ['hero_search']);
        $candidate = $this->theme(ThemeStatus::Active, []);
        $site = Site::factory()->create(['theme_id' => $current->id]);
        $definition = BlockDefinition::factory()->create([
            'code' => 'hero_search',
            'status' => BlockStatus::Active,
            'supported_page_types_json' => ['home'],
        ]);
        SiteHomeBlock::factory()->create([
            'site_id' => $site->id,
            'block_code' => $definition->code,
            'position' => 1,
            'enabled' => true,
        ]);

        try {
            app(ActivateThemeAction::class)->handle($site, $candidate);
            $this->fail('A theme missing an enabled homepage block capability was activated.');
        } catch (CannotActivateThemeException $exception) {
            $this->assertStringContainsString('hero_search', $exception->getMessage());
            $this->assertNotNull($exception->getPrevious());
        }

        $this->assertSame($current->id, $site->fresh()->theme_id);
    }

    public function test_theme_supporting_enabled_homepage_blocks_can_be_activated(): void
    {
        $current = $this->theme(ThemeStatus::Active, ['hero_search']);
        $candidate = $this->theme(ThemeStatus::Active, ['hero_search']);
        $site = Site::factory()->create(['theme_id' => $current->id]);
        $definition = BlockDefinition::factory()->create([
            'code' => 'hero_search',
            'status' => BlockStatus::Active,
            'supported_page_types_json' => ['home'],
        ]);
        SiteHomeBlock::factory()->create([
            'site_id' => $site->id,
            'block_code' => $definition->code,
            'position' => 1,
            'enabled' => true,
        ]);

        app(ActivateThemeAction::class)->handle($site, $candidate);

        $this->assertSame($candidate->id, $site->fresh()->theme_id);
    }

    /** @param list<string> $supports */
    private function theme(ThemeStatus $status, array $supports): Theme
    {
        $theme = Theme::factory()->create(['status' => $status]);
        ThemeManifestRecord::query()->create([
            'theme_id' => $theme->id,
            'manifest_json' => [
                'code' => $theme->code,
                'name' => $theme->name,
                'supports' => $supports,
                'layouts' => ['home' => 'home-clean'],
            ],
            'supports_json' => $supports,
            'layouts_json' => ['home' => 'home-clean'],
        ]);

        return $theme;
    }
}
