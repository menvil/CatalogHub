<?php

namespace Tests\Feature\Themes;

use App\Domains\Themes\Services\BlockCompatibilityValidator;
use App\Enums\BlockStatus;
use App\Enums\ThemeStatus;
use App\Exceptions\Themes\CannotUseBlockException;
use App\Models\BlockDefinition;
use App\Models\Site;
use App\Models\SiteFeature;
use App\Models\Theme;
use App\Models\ThemeManifestRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockCompatibilityValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_supported_home_block_is_accepted(): void
    {
        [$site] = $this->siteWithTheme(['hero_search']);
        $this->block('hero_search');

        app(BlockCompatibilityValidator::class)->validate($site, 'hero_search');

        $this->addToAssertionCount(1);
    }

    public function test_block_not_supported_by_theme_is_rejected(): void
    {
        [$site] = $this->siteWithTheme([]);
        $this->block('hero_search');

        $this->expectException(CannotUseBlockException::class);
        $this->expectExceptionMessage('does not support');

        app(BlockCompatibilityValidator::class)->validate($site, 'hero_search');
    }

    public function test_block_requiring_disabled_feature_is_rejected(): void
    {
        [$site] = $this->siteWithTheme(['latest_reviews']);
        $this->block('latest_reviews', ['home'], ['reviews']);

        $this->expectException(CannotUseBlockException::class);
        $this->expectExceptionMessage('requires enabled feature reviews');

        app(BlockCompatibilityValidator::class)->validate($site, 'latest_reviews');
    }

    public function test_block_for_wrong_page_type_is_rejected(): void
    {
        [$site] = $this->siteWithTheme(['hero_search']);
        $this->block('hero_search', ['home']);

        $this->expectException(CannotUseBlockException::class);
        $this->expectExceptionMessage('does not support page type product');

        app(BlockCompatibilityValidator::class)->validate($site, 'hero_search', 'product');
    }

    public function test_block_without_active_site_theme_is_rejected(): void
    {
        $site = Site::factory()->create();
        $this->block('hero_search');

        $this->expectException(CannotUseBlockException::class);
        $this->expectExceptionMessage('does not have an active theme');

        app(BlockCompatibilityValidator::class)->validate($site, 'hero_search');
    }

    public function test_enabled_required_feature_allows_block(): void
    {
        [$site] = $this->siteWithTheme(['latest_reviews']);
        SiteFeature::query()->create(['site_id' => $site->id, 'feature_key' => 'reviews', 'is_enabled' => true]);
        $this->block('latest_reviews', ['home'], ['reviews']);

        app(BlockCompatibilityValidator::class)->validate($site, 'latest_reviews');

        $this->addToAssertionCount(1);
    }

    /** @param list<string> $supports */
    private function siteWithTheme(array $supports): array
    {
        $theme = Theme::factory()->create(['status' => ThemeStatus::Active]);
        ThemeManifestRecord::query()->create([
            'theme_id' => $theme->id,
            'manifest_json' => ['code' => $theme->code, 'name' => $theme->name, 'supports' => $supports, 'layouts' => ['home' => 'home-clean']],
            'supports_json' => $supports,
            'layouts_json' => ['home' => 'home-clean'],
        ]);

        return [Site::factory()->create(['theme_id' => $theme->id]), $theme];
    }

    /**
     * @param  list<string>  $pages
     * @param  list<string>  $features
     */
    private function block(string $code, array $pages = ['home'], array $features = []): BlockDefinition
    {
        return BlockDefinition::factory()->create([
            'code' => $code,
            'status' => BlockStatus::Active,
            'supported_page_types_json' => $pages,
            'required_features_json' => $features,
        ]);
    }
}
