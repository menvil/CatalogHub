<?php

namespace Tests\Feature\Demo;

use App\Enums\SiteMode;
use App\Models\Site;
use Database\Seeders\Demo\KeyboardsSiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KeyboardsSiteSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_a_single_category_keyboards_site(): void
    {
        $this->seed(KeyboardsSiteSeeder::class);

        $site = Site::query()->where('code', 'keyboard-guru-demo')->firstOrFail();
        $enabledCategorySlugs = DB::table('site_categories')
            ->join('central_categories', 'central_categories.id', '=', 'site_categories.central_category_id')
            ->where('site_categories.site_id', $site->id)
            ->where('site_categories.is_enabled', true)
            ->pluck('central_categories.slug')
            ->all();

        $this->assertSame(SiteMode::SingleCategory, $site->mode);
        $this->assertSame(['keyboards'], $enabledCategorySlugs);
        $this->assertTrue($site->theme?->isActive());
        $this->assertSame(['hero_search', 'top_products'], $site->homeBlocks()->orderBy('position')->pluck('block_code')->all());
    }
}
