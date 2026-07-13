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

class PollBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BlockRegistrySeeder::class);
    }

    public function test_poll_block_is_registered_for_supported_page_types(): void
    {
        $block = BlockDefinition::query()->where('code', 'poll_block')->firstOrFail();

        $this->assertTrue($block->isActive());
        $this->assertSame(['home', 'category', 'product'], $block->supported_page_types_json);
        $this->assertSame(['polls'], $block->required_features_json);
        $this->assertSame('string', $block->config_schema_json['title']);
        $this->assertSame(['type' => 'integer', 'nullable' => true], $block->config_schema_json['poll_id']);
        $this->assertSame('inline|sidebar|card', $block->config_schema_json['placement']);
        $this->assertTrue(View::exists('components.blocks.poll-block'));
    }

    public function test_poll_block_requires_enabled_polls_feature(): void
    {
        $site = $this->siteWithThemeSupport();
        $validator = app(BlockCompatibilityValidator::class);

        try {
            $validator->validate($site, 'poll_block');
            $this->fail('Disabled polls feature should block poll_block.');
        } catch (CannotUseBlockException $exception) {
            $this->assertStringContainsString('polls', $exception->getMessage());
        }

        SiteFeature::query()->create(['site_id' => $site->id, 'feature_key' => 'polls', 'is_enabled' => true]);
        foreach (['home', 'category', 'product'] as $pageType) {
            $validator->validate($site, 'poll_block', $pageType);
        }
        $this->addToAssertionCount(3);
    }

    private function siteWithThemeSupport(): Site
    {
        $theme = Theme::factory()->create(['status' => ThemeStatus::Active]);
        ThemeManifestRecord::query()->create([
            'theme_id' => $theme->id,
            'manifest_json' => ['code' => $theme->code, 'name' => $theme->name, 'supports' => ['poll_block'], 'layouts' => ['home' => 'home-clean']],
            'supports_json' => ['poll_block'],
            'layouts_json' => ['home' => 'home-clean'],
        ]);

        return Site::factory()->create(['theme_id' => $theme->id]);
    }
}
