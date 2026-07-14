<?php

namespace Database\Seeders\Demo;

use App\Enums\SiteMode;
use Database\Seeders\BlockRegistrySeeder;
use Illuminate\Database\Seeder;

class MultiCategorySiteSeeder extends Seeder
{
    public function run(DemoSiteSeederSupport $demo): void
    {
        $this->call(BlockRegistrySeeder::class);

        $demo->site(
            code: 'tech-compare-global',
            name: 'Tech Compare Global',
            domain: 'tech-compare.test',
            mode: SiteMode::MultiCategory,
            categorySlugs: ['monitors', 'keyboards', 'mice'],
            blocks: [
                ['code' => 'hero_search', 'config' => ['title' => 'Find the right technology', 'subtitle' => 'Compare products using clear specifications.', 'search_placeholder' => 'Search products']],
                ['code' => 'popular_categories', 'config' => ['title' => 'Popular categories', 'limit' => 3, 'layout' => 'grid']],
                ['code' => 'top_products', 'config' => ['title' => 'Top products', 'limit' => 8, 'source' => 'popular', 'layout' => 'grid']],
            ],
        );
    }
}
