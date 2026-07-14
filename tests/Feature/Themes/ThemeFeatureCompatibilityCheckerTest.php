<?php

namespace Tests\Feature\Themes;

use App\Domains\Themes\Services\ThemeFeatureCompatibilityChecker;
use App\Models\Site;
use App\Models\SiteFeature;
use App\Models\Theme;
use App\Models\ThemeManifestRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeFeatureCompatibilityCheckerTest extends TestCase
{
    use RefreshDatabase;

    public function test_theme_is_compatible_when_all_enabled_features_are_supported(): void
    {
        $site = Site::factory()->create();
        SiteFeature::query()->create(['site_id' => $site->id, 'feature_key' => 'reviews', 'is_enabled' => true]);
        SiteFeature::query()->create(['site_id' => $site->id, 'feature_key' => 'comparison', 'is_enabled' => true]);
        SiteFeature::query()->create(['site_id' => $site->id, 'feature_key' => 'polls', 'is_enabled' => false]);
        $theme = $this->themeWithCapabilities(['review_form', 'comparison_block']);

        $result = app(ThemeFeatureCompatibilityChecker::class)->check($site, $theme);

        $this->assertTrue($result->compatible);
        $this->assertSame([], $result->missingFeatures);
        $this->assertSame([], $result->warnings);
    }

    public function test_missing_enabled_features_are_returned_in_detail(): void
    {
        $site = Site::factory()->create();
        SiteFeature::query()->create(['site_id' => $site->id, 'feature_key' => 'polls', 'is_enabled' => true]);
        SiteFeature::query()->create(['site_id' => $site->id, 'feature_key' => 'leads', 'is_enabled' => false]);
        $theme = $this->themeWithCapabilities(['hero_search']);

        $result = app(ThemeFeatureCompatibilityChecker::class)->check($site, $theme);

        $this->assertFalse($result->compatible);
        $this->assertSame(['polls'], $result->missingFeatures);
    }

    public function test_any_supported_price_capability_satisfies_price_related_features(): void
    {
        $site = Site::factory()->create();
        SiteFeature::query()->create(['site_id' => $site->id, 'feature_key' => 'local_offers', 'is_enabled' => true]);
        SiteFeature::query()->create(['site_id' => $site->id, 'feature_key' => 'external_price_widget', 'is_enabled' => true]);
        $theme = $this->themeWithCapabilities(['price_block']);

        $this->assertTrue(app(ThemeFeatureCompatibilityChecker::class)->check($site, $theme)->compatible);
    }

    public function test_theme_without_manifest_is_incompatible_with_warning(): void
    {
        $site = Site::factory()->create();
        SiteFeature::query()->create(['site_id' => $site->id, 'feature_key' => 'reviews', 'is_enabled' => true]);

        $result = app(ThemeFeatureCompatibilityChecker::class)->check($site, Theme::factory()->create());

        $this->assertFalse($result->compatible);
        $this->assertSame(['reviews'], $result->missingFeatures);
        $this->assertStringContainsString('does not have a manifest', $result->warnings[0]);
    }

    /** @param list<string> $capabilities */
    private function themeWithCapabilities(array $capabilities): Theme
    {
        $theme = Theme::factory()->create();
        ThemeManifestRecord::query()->create([
            'theme_id' => $theme->id,
            'manifest_json' => [
                'code' => $theme->code,
                'name' => $theme->name,
                'supports' => $capabilities,
                'layouts' => ['home' => 'home-clean'],
            ],
            'supports_json' => $capabilities,
            'layouts_json' => ['home' => 'home-clean'],
        ]);

        return $theme;
    }
}
