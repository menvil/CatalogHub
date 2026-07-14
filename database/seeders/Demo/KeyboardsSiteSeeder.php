<?php

namespace Database\Seeders\Demo;

use App\Enums\SiteMode;
use Database\Seeders\BlockRegistrySeeder;
use Illuminate\Database\Seeder;

class KeyboardsSiteSeeder extends Seeder
{
    public function run(DemoSiteSeederSupport $demo): void
    {
        $this->call(BlockRegistrySeeder::class);

        $demo->site(
            code: 'keyboard-guru-demo',
            name: 'Keyboard Guru Demo',
            domain: 'keyboards.test',
            mode: SiteMode::SingleCategory,
            categorySlugs: ['keyboards'],
            blocks: [
                ['code' => 'hero_search', 'config' => ['title' => 'Find your ideal keyboard', 'subtitle' => 'Compare layouts, switches, and connectivity.', 'search_placeholder' => 'Search keyboards']],
                ['code' => 'top_products', 'config' => ['title' => 'Top keyboards', 'limit' => 8, 'source' => 'popular', 'category_id' => null, 'layout' => 'grid']],
            ],
        );
    }
}
