<?php

namespace Tests\Feature\Domains\Projections;

use App\Domains\Projections\SiteSyncService;
use App\Enums\CentralCategoryStatus;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Models\SiteProductProjection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JsonException;
use Tests\TestCase;

class SiteSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_product_projection_search_sitemap_and_audit_records(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en']);
        $product = CentralProduct::factory()->create([
            'name' => 'Projection Product',
            'slug' => 'projection-product',
            'status' => CentralProductStatus::Active,
            'version' => 7,
        ]);

        app(SiteSyncService::class)->syncProduct($site, $product, 'en');

        $this->assertDatabaseHas('site_product_projections', [
            'site_id' => $site->id,
            'locale' => 'en',
            'central_product_id' => $product->id,
            'central_product_version' => 7,
            'status' => 'active',
            'stale_at' => null,
            'failed_at' => null,
        ]);
        $this->assertDatabaseHas('site_search_documents', [
            'site_id' => $site->id,
            'locale' => 'en',
            'document_type' => 'product',
            'document_id' => $product->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('site_sitemap_urls', [
            'site_id' => $site->id,
            'locale' => 'en',
            'entity_type' => 'product',
            'entity_id' => $product->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('projection_jobs', [
            'site_id' => $site->id,
            'job_type' => 'product',
            'target_id' => $product->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('projection_logs', ['event' => 'started', 'entity_type' => 'product']);
        $this->assertDatabaseHas('projection_logs', ['event' => 'completed', 'entity_type' => 'product']);
    }

    public function test_it_syncs_category_projection_search_and_sitemap_records(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en']);
        $category = CentralCategory::factory()->create([
            'name' => 'Monitors',
            'slug' => 'monitors',
            'status' => CentralCategoryStatus::Active,
        ]);

        app(SiteSyncService::class)->syncCategory($site, $category, 'en');

        $this->assertDatabaseHas('site_category_projections', [
            'site_id' => $site->id,
            'locale' => 'en',
            'central_category_id' => $category->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('site_search_documents', [
            'site_id' => $site->id,
            'locale' => 'en',
            'document_type' => 'category',
            'document_id' => $category->id,
        ]);
        $this->assertDatabaseHas('site_sitemap_urls', [
            'site_id' => $site->id,
            'locale' => 'en',
            'entity_type' => 'category',
            'entity_id' => $category->id,
        ]);
    }

    public function test_successful_product_rebuild_clears_stale_state_without_creating_a_duplicate(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en']);
        $product = CentralProduct::factory()->create([
            'status' => CentralProductStatus::Active,
        ]);
        SiteProductProjection::create([
            'site_id' => $site->id,
            'locale' => 'en',
            'central_product_id' => $product->id,
            'slug' => $product->slug,
            'status' => 'stale',
            'payload_json' => [],
            'stale_at' => now(),
        ]);

        app(SiteSyncService::class)->syncProduct($site, $product, 'en');

        $this->assertDatabaseCount('site_product_projections', 1);
        $this->assertDatabaseHas('site_product_projections', [
            'site_id' => $site->id,
            'locale' => 'en',
            'central_product_id' => $product->id,
            'status' => 'active',
            'stale_at' => null,
        ]);
    }

    public function test_it_marks_the_job_failed_and_logs_builder_failures(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en']);
        $product = CentralProduct::factory()->create([
            'name' => "Invalid \xB1 UTF-8",
            'status' => CentralProductStatus::Active,
        ]);

        try {
            app(SiteSyncService::class)->syncProduct($site, $product, 'en');
            $this->fail('Expected malformed projection data to fail JSON encoding.');
        } catch (JsonException) {
            // The service must preserve the original build exception after auditing it.
        }

        $this->assertDatabaseHas('projection_jobs', [
            'site_id' => $site->id,
            'job_type' => 'product',
            'target_id' => $product->id,
            'status' => 'failed',
        ]);
        $this->assertDatabaseHas('projection_logs', [
            'site_id' => $site->id,
            'event' => 'failed',
            'level' => 'error',
            'entity_type' => 'product',
            'entity_id' => $product->id,
        ]);
    }

    public function test_site_sync_records_item_failures_and_continues_with_remaining_products(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en']);
        $invalid = CentralProduct::factory()->create([
            'name' => "Invalid \xB1 UTF-8",
            'status' => CentralProductStatus::Active,
        ]);
        $valid = CentralProduct::factory()->create([
            'status' => CentralProductStatus::Active,
        ]);

        foreach ([$invalid, $valid] as $product) {
            SiteProduct::create([
                'site_id' => $site->id,
                'central_product_id' => $product->id,
                'visibility' => 'visible',
            ]);
        }

        $counts = app(SiteSyncService::class)->syncSite(
            $site,
            locale: 'en',
            productsOnly: true,
        );

        $this->assertSame(1, $counts['products']);
        $this->assertCount(1, $counts['failures']);
        $this->assertSame($invalid->id, $counts['failures'][0]['entity_id']);
        $this->assertDatabaseHas('site_product_projections', [
            'site_id' => $site->id,
            'central_product_id' => $valid->id,
        ]);
        $this->assertDatabaseHas('projection_jobs', [
            'site_id' => $site->id,
            'job_type' => 'site',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('projection_logs', [
            'site_id' => $site->id,
            'event' => 'item_failed',
            'entity_type' => 'product',
            'entity_id' => $invalid->id,
        ]);
    }
}
