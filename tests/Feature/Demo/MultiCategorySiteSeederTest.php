<?php

namespace Tests\Feature\Demo;

use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use App\Models\Site;
use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MultiCategorySiteSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_an_active_multi_category_site_with_theme_locales_categories_and_blocks(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);

        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();

        $this->assertSame(SiteMode::MultiCategory, $site->mode);
        $this->assertSame(SiteStatus::Active, $site->status);
        $this->assertSame('en-US', $site->default_locale);
        $this->assertTrue($site->theme?->isActive());
        $this->assertSame(3, DB::table('site_categories')->where('site_id', $site->id)->where('is_enabled', true)->count());
        $this->assertSame(3, $site->homeBlocks()->where('enabled', true)->count());
        $this->assertDatabaseHas('site_locales', [
            'site_id' => $site->id,
            'locale_code' => 'en-US',
            'is_default' => true,
            'is_enabled' => true,
        ]);
    }
}
