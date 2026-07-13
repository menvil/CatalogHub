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

class LatestReviewsBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BlockRegistrySeeder::class);
    }

    public function test_latest_reviews_is_registered_and_requires_reviews(): void
    {
        $block = BlockDefinition::query()->where('code', 'latest_reviews')->firstOrFail();

        $this->assertTrue($block->isActive());
        $this->assertSame(['home'], $block->supported_page_types_json);
        $this->assertSame(['reviews'], $block->required_features_json);
        $this->assertSame('integer', $block->config_schema_json['limit']);
        $this->assertSame('cards|compact', $block->config_schema_json['layout']);
        $this->assertTrue(View::exists('components.blocks.latest-reviews'));
    }

    public function test_latest_reviews_is_blocked_until_reviews_are_enabled(): void
    {
        $site = $this->siteWithThemeSupport();
        $validator = app(BlockCompatibilityValidator::class);

        try {
            $validator->validate($site, 'latest_reviews');
            $this->fail('Disabled reviews feature should block latest_reviews.');
        } catch (CannotUseBlockException $exception) {
            $this->assertStringContainsString('reviews', $exception->getMessage());
        }

        SiteFeature::query()->create([
            'site_id' => $site->id,
            'feature_key' => 'reviews',
            'is_enabled' => true,
        ]);
        $validator->validate($site, 'latest_reviews');
        $this->addToAssertionCount(1);
    }

    private function siteWithThemeSupport(): Site
    {
        $theme = Theme::factory()->create(['status' => ThemeStatus::Active]);
        ThemeManifestRecord::query()->create([
            'theme_id' => $theme->id,
            'manifest_json' => ['code' => $theme->code, 'name' => $theme->name, 'supports' => ['latest_reviews'], 'layouts' => ['home' => 'home-clean']],
            'supports_json' => ['latest_reviews'],
            'layouts_json' => ['home' => 'home-clean'],
        ]);

        return Site::factory()->create(['theme_id' => $theme->id]);
    }
}
