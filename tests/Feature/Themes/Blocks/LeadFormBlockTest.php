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

class LeadFormBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BlockRegistrySeeder::class);
    }

    public function test_lead_form_is_registered_for_home_and_product_pages(): void
    {
        $block = BlockDefinition::query()->where('code', 'lead_form')->firstOrFail();

        $this->assertTrue($block->isActive());
        $this->assertSame(['home', 'product'], $block->supported_page_types_json);
        $this->assertSame(['leads'], $block->required_features_json);
        $this->assertSame('repair|buying_advice|accessory_request|business_inquiry', $block->config_schema_json['lead_type']);
        $this->assertTrue(View::exists('components.blocks.lead-form'));
    }

    public function test_lead_form_requires_enabled_leads_feature(): void
    {
        $site = $this->siteWithThemeSupport();
        $validator = app(BlockCompatibilityValidator::class);

        try {
            $validator->validate($site, 'lead_form');
            $this->fail('Disabled leads feature should block lead_form.');
        } catch (CannotUseBlockException $exception) {
            $this->assertStringContainsString('leads', $exception->getMessage());
        }

        SiteFeature::query()->create(['site_id' => $site->id, 'feature_key' => 'leads', 'is_enabled' => true]);
        $validator->validate($site, 'lead_form', 'home');
        $validator->validate($site, 'lead_form', 'product');
        $this->addToAssertionCount(2);
    }

    private function siteWithThemeSupport(): Site
    {
        $theme = Theme::factory()->create(['status' => ThemeStatus::Active]);
        ThemeManifestRecord::query()->create([
            'theme_id' => $theme->id,
            'manifest_json' => ['code' => $theme->code, 'name' => $theme->name, 'supports' => ['lead_form'], 'layouts' => ['home' => 'home-clean']],
            'supports_json' => ['lead_form'],
            'layouts_json' => ['home' => 'home-clean'],
        ]);

        return Site::factory()->create(['theme_id' => $theme->id]);
    }
}
