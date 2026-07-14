<?php

namespace Tests\Feature\Domains\Projections;

use App\Domains\Projections\Builders\SitemapBuilder;
use App\Domains\Projections\DTO\CategoryProjectionData;
use App\Domains\Projections\DTO\ProductProjectionData;
use App\Models\Site;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_product_sitemap_record_from_projection_metadata(): void
    {
        $site = Site::factory()->create(['domain' => 'example.test']);
        $builtAt = CarbonImmutable::parse('2026-07-14T10:00:00Z');
        $projection = new ProductProjectionData(
            siteId: $site->id,
            locale: 'en',
            centralProductId: 123,
            slug: 'lg-ultragear-27gp850-b',
            title: 'LG UltraGear 27GP850-B',
            status: 'active',
            payload: [],
            seo: ['canonical_url' => 'https://example.test/products/lg-ultragear-27gp850-b'],
            media: [],
            checksum: 'product-checksum',
            builtAt: $builtAt,
        );

        $builder = app(SitemapBuilder::class);
        $first = $builder->fromProductProjection($site, $projection);
        $second = $builder->fromProductProjection($site, $projection);

        $this->assertSame('https://example.test/products/lg-ultragear-27gp850-b', $first->url);
        $this->assertSame('product', $first->entityType);
        $this->assertSame(123, $first->entityId);
        $this->assertSame('weekly', $first->changefreq);
        $this->assertSame(0.8, $first->priority);
        $this->assertTrue($builtAt->equalTo($first->lastmodAt));
        $this->assertSame($first->checksum, $second->checksum);
    }

    public function test_it_builds_a_category_sitemap_record_and_marks_non_active_projection_inactive(): void
    {
        $site = Site::factory()->create(['domain' => 'catalog.example']);
        $projection = new CategoryProjectionData(
            siteId: $site->id,
            locale: 'de-DE',
            centralCategoryId: 20,
            parentCategoryId: null,
            slug: 'monitore',
            title: 'Monitore',
            status: 'pending',
            payload: [],
            seo: [],
            facets: [],
            comparison: [],
            checksum: 'category-checksum',
        );

        $record = app(SitemapBuilder::class)->fromCategoryProjection($site, $projection);

        $this->assertSame('https://catalog.example/categories/monitore', $record->url);
        $this->assertSame('category', $record->entityType);
        $this->assertSame('inactive', $record->status);
        $this->assertSame('daily', $record->changefreq);
    }
}
