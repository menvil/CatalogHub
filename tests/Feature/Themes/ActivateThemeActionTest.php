<?php

namespace Tests\Feature\Themes;

use App\Domains\Themes\Actions\ActivateThemeAction;
use App\Enums\ThemeStatus;
use App\Exceptions\Themes\CannotActivateThemeException;
use App\Models\Site;
use App\Models\SiteFeature;
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
