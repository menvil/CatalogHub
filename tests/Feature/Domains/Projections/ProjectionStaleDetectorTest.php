<?php

namespace Tests\Feature\Domains\Projections;

use App\Domains\Projections\ProjectionStaleDetector;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectionStaleDetectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_product_projections_and_search_documents_stale(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $projection = SiteProductProjection::create([
            'site_id' => $site->id,
            'locale' => 'en',
            'central_product_id' => $product->id,
            'central_product_version' => $product->updated_at?->timestamp,
            'slug' => $product->slug,
            'status' => 'active',
            'payload_json' => [],
        ]);
        $searchDocument = SiteSearchDocument::create([
            'site_id' => $site->id,
            'locale' => 'en',
            'document_type' => 'product',
            'document_id' => $product->id,
            'status' => 'active',
        ]);

        $count = app(ProjectionStaleDetector::class)
            ->markStaleForProduct($product, 'media_updated');

        $this->assertSame(1, $count);
        $this->assertSame('stale', $projection->refresh()->getRawOriginal('status'));
        $this->assertNotNull($projection->stale_at);
        $this->assertSame('stale', $searchDocument->refresh()->getRawOriginal('status'));
        $this->assertDatabaseHas('projection_logs', [
            'site_id' => $site->id,
            'event' => 'stale',
            'message' => 'media_updated',
            'entity_type' => 'product',
            'entity_id' => $product->id,
        ]);
    }

    public function test_it_marks_category_projections_and_search_documents_stale(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        $projection = SiteCategoryProjection::create([
            'site_id' => $site->id,
            'locale' => 'en',
            'central_category_id' => $category->id,
            'central_category_version' => $category->updated_at?->timestamp,
            'slug' => $category->slug,
            'status' => 'active',
            'payload_json' => [],
        ]);
        $searchDocument = SiteSearchDocument::create([
            'site_id' => $site->id,
            'locale' => 'en',
            'document_type' => 'category',
            'document_id' => $category->id,
            'status' => 'active',
        ]);

        app(ProjectionStaleDetector::class)->markStaleForCategory($category);

        $this->assertSame('stale', $projection->refresh()->getRawOriginal('status'));
        $this->assertSame('stale', $searchDocument->refresh()->getRawOriginal('status'));
    }

    public function test_it_detects_only_changed_source_versions_for_the_site(): void
    {
        $site = Site::factory()->create();
        $changed = CentralProduct::factory()->create();
        $current = CentralProduct::factory()->create();
        $unversioned = CentralProduct::factory()->create();
        DB::table('central_products')->where('id', $unversioned->id)->update(['updated_at' => null]);
        $changedCategory = CentralCategory::factory()->create();
        $changedProjection = SiteProductProjection::create([
            'site_id' => $site->id,
            'locale' => 'en',
            'central_product_id' => $changed->id,
            'central_product_version' => max(0, (int) $changed->updated_at?->timestamp - 1),
            'slug' => $changed->slug,
            'status' => 'active',
            'payload_json' => [],
        ]);
        $currentProjection = SiteProductProjection::create([
            'site_id' => $site->id,
            'locale' => 'en',
            'central_product_id' => $current->id,
            'central_product_version' => $current->updated_at?->timestamp,
            'slug' => $current->slug,
            'status' => 'active',
            'payload_json' => [],
        ]);
        $unversionedProjection = SiteProductProjection::create([
            'site_id' => $site->id,
            'locale' => 'en',
            'central_product_id' => $unversioned->id,
            'central_product_version' => null,
            'slug' => $unversioned->slug,
            'status' => 'active',
            'payload_json' => [],
        ]);
        $changedCategoryProjection = SiteCategoryProjection::create([
            'site_id' => $site->id,
            'locale' => 'en',
            'central_category_id' => $changedCategory->id,
            'central_category_version' => max(0, (int) $changedCategory->updated_at?->timestamp - 1),
            'slug' => $changedCategory->slug,
            'status' => 'active',
            'payload_json' => [],
        ]);

        $counts = app(ProjectionStaleDetector::class)->detectStaleForSite($site);

        $this->assertSame(['products' => 1, 'categories' => 1], $counts);
        $this->assertSame('stale', $changedProjection->refresh()->getRawOriginal('status'));
        $this->assertSame('active', $currentProjection->refresh()->getRawOriginal('status'));
        $this->assertSame('active', $unversionedProjection->refresh()->getRawOriginal('status'));
        $this->assertSame('stale', $changedCategoryProjection->refresh()->getRawOriginal('status'));
    }

    public function test_it_can_mark_all_site_projections_stale(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $category = CentralCategory::factory()->create();
        SiteProductProjection::create([
            'site_id' => $site->id,
            'locale' => 'en',
            'central_product_id' => $product->id,
            'slug' => $product->slug,
            'status' => 'active',
            'payload_json' => [],
        ]);
        SiteCategoryProjection::create([
            'site_id' => $site->id,
            'locale' => 'en',
            'central_category_id' => $category->id,
            'slug' => $category->slug,
            'status' => 'active',
            'payload_json' => [],
        ]);

        $counts = app(ProjectionStaleDetector::class)->markStaleForSite($site, 'site_override_updated');

        $this->assertSame(1, $counts['products']);
        $this->assertSame(1, $counts['categories']);
        $this->assertDatabaseHas('site_product_projections', ['site_id' => $site->id, 'status' => 'stale']);
        $this->assertDatabaseHas('site_category_projections', ['site_id' => $site->id, 'status' => 'stale']);
    }
}
