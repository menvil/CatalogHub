<?php

namespace Tests\Feature\Console;

use App\Enums\CentralCategoryStatus;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncCategoryProjectionCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_one_category_for_a_site_and_locale(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active]);

        $this->artisan('cataloghub:sync-category', [
            'site' => $site->id,
            'category' => $category->id,
            '--locale' => 'en',
        ])
            ->expectsOutputToContain('Category projection synced')
            ->assertSuccessful();

        $this->assertDatabaseHas('site_category_projections', [
            'site_id' => $site->id,
            'central_category_id' => $category->id,
            'locale' => 'en',
        ]);
    }

    public function test_with_products_syncs_only_visible_site_products_in_the_category(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active]);
        $visible = CentralProduct::factory()->for($category, 'category')->create(['status' => CentralProductStatus::Active]);
        $hidden = CentralProduct::factory()->for($category, 'category')->create(['status' => CentralProductStatus::Active]);
        SiteProduct::create([
            'site_id' => $site->id,
            'central_product_id' => $visible->id,
            'visibility' => 'visible',
        ]);
        SiteProduct::create([
            'site_id' => $site->id,
            'central_product_id' => $hidden->id,
            'visibility' => 'hidden',
        ]);

        $this->artisan('cataloghub:sync-category', [
            'site' => $site->id,
            'category' => $category->id,
            '--locale' => 'en',
            '--with-products' => true,
        ])
            ->expectsOutputToContain('products=1 failures=0')
            ->assertSuccessful();

        $this->assertDatabaseHas('site_product_projections', [
            'site_id' => $site->id,
            'central_product_id' => $visible->id,
        ]);
        $this->assertDatabaseMissing('site_product_projections', [
            'site_id' => $site->id,
            'central_product_id' => $hidden->id,
        ]);
    }

    public function test_with_products_continues_after_an_individual_product_failure(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active]);
        $invalid = CentralProduct::factory()->for($category, 'category')->create([
            'name' => "Invalid \xB1 UTF-8",
            'status' => CentralProductStatus::Active,
        ]);
        $valid = CentralProduct::factory()->for($category, 'category')->create([
            'status' => CentralProductStatus::Active,
        ]);

        foreach ([$invalid, $valid] as $product) {
            SiteProduct::create([
                'site_id' => $site->id,
                'central_product_id' => $product->id,
                'visibility' => 'visible',
            ]);
        }

        $this->artisan('cataloghub:sync-category', [
            'site' => $site->id,
            'category' => $category->id,
            '--locale' => 'en',
            '--with-products' => true,
        ])
            ->expectsOutputToContain("Product projection failed: product={$invalid->id}")
            ->expectsOutputToContain('products=1 failures=1')
            ->assertExitCode(2);

        $this->assertDatabaseHas('site_category_projections', [
            'site_id' => $site->id,
            'central_category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('site_product_projections', [
            'site_id' => $site->id,
            'central_product_id' => $valid->id,
        ]);
        $this->assertDatabaseMissing('site_product_projections', [
            'site_id' => $site->id,
            'central_product_id' => $invalid->id,
        ]);
    }
}
