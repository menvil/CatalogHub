<?php

namespace Database\Seeders\Demo;

use App\Enums\SiteMode;
use Database\Seeders\BlockRegistrySeeder;
use Illuminate\Database\Seeder;

class MonitorsSiteSeeder extends Seeder
{
    public function run(DemoSiteSeederSupport $demo): void
    {
        $this->call(BlockRegistrySeeder::class);

        $demo->site(
            code: 'monitor-compare-demo',
            name: 'Monitor Compare Demo',
            domain: 'monitors.test',
            mode: SiteMode::SingleCategory,
            categorySlugs: ['monitors'],
            blocks: [
                ['code' => 'hero_search', 'config' => ['title' => 'Choose your next monitor', 'subtitle' => 'Compare display specifications side by side.', 'search_placeholder' => 'Search monitors']],
                ['code' => 'top_products', 'config' => ['title' => 'Top monitors', 'limit' => 8, 'source' => 'popular', 'category_id' => null, 'layout' => 'grid']],
            ],
        );
    }
}
