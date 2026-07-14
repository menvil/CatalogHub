<?php

namespace Tests\Feature\Console;

use App\Enums\CentralCategoryStatus;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SyncSiteProjectionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_enabled_categories_and_visible_products_for_one_locale(): void
    {
        $site = Site::factory()->create(['default_locale' => 'de']);
        $category = CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active]);
        $product = CentralProduct::factory()
            ->for($category, 'category')
            ->create(['status' => CentralProductStatus::Active]);

        DB::table('site_categories')->insert([
            'site_id' => $site->id,
            'central_category_id' => $category->id,
            'is_enabled' => true,
            'position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        SiteProduct::create([
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'visibility' => 'visible',
        ]);

        $this->artisan('cataloghub:sync-site', [
            'site' => $site->id,
            '--locale' => 'en',
        ])
            ->expectsOutputToContain('locales=1 categories=1 products=1')
            ->assertSuccessful();

        $this->assertDatabaseHas('site_category_projections', [
            'site_id' => $site->id,
            'central_category_id' => $category->id,
            'locale' => 'en',
        ]);
        $this->assertDatabaseHas('site_product_projections', [
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'locale' => 'en',
        ]);
    }

    public function test_products_only_skips_category_projections(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active]);
        $product = CentralProduct::factory()
            ->for($category, 'category')
            ->create(['status' => CentralProductStatus::Active]);

        DB::table('site_categories')->insert([
            'site_id' => $site->id,
            'central_category_id' => $category->id,
            'is_enabled' => true,
            'position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        SiteProduct::create([
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'visibility' => 'visible',
        ]);

        $this->artisan('cataloghub:sync-site', [
            'site' => $site->id,
            '--locale' => 'en',
            '--products-only' => true,
        ])
            ->expectsOutputToContain('categories=0 products=1')
            ->assertSuccessful();

        $this->assertDatabaseMissing('site_category_projections', [
            'site_id' => $site->id,
            'central_category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('site_product_projections', [
            'site_id' => $site->id,
            'central_product_id' => $product->id,
        ]);
    }

    public function test_mutually_exclusive_scope_options_are_rejected(): void
    {
        $site = Site::factory()->create();

        $this->artisan('cataloghub:sync-site', [
            'site' => $site->id,
            '--products-only' => true,
            '--categories-only' => true,
        ])
            ->expectsOutputToContain('cannot be combined')
            ->assertExitCode(2);
    }
}
