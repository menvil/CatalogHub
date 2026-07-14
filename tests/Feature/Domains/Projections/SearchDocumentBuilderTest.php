<?php

namespace Tests\Feature\Domains\Projections;

use App\Domains\Projections\Builders\SearchDocumentBuilder;
use App\Domains\Projections\DTO\CategoryProjectionData;
use App\Domains\Projections\DTO\ProductProjectionData;
use App\Domains\Projections\Enums\ProjectionStatus;
use Tests\TestCase;

class SearchDocumentBuilderTest extends TestCase
{
    public function test_it_builds_a_product_search_document_with_canonical_filter_values(): void
    {
        $projection = new ProductProjectionData(
            siteId: 1,
            locale: 'en',
            centralProductId: 123,
            slug: 'lg-ultragear-27gp850-b',
            title: 'LG UltraGear 27GP850-B',
            status: ProjectionStatus::Active,
            payload: [
                'product' => ['id' => 123, 'title' => 'LG UltraGear 27GP850-B', 'model' => '27GP850-B'],
                'brand' => ['id' => 10, 'name' => 'LG'],
                'category' => ['id' => 20, 'label' => 'Monitors'],
                'spec_sections' => [[
                    'attributes' => [
                        [
                            'code' => 'refresh_rate',
                            'canonical_value' => 165,
                            'canonical_unit' => 'hertz',
                            'display_value' => '165 Hz',
                            'is_filterable' => true,
                            'is_sortable' => true,
                            'is_searchable' => true,
                        ],
                        [
                            'code' => 'internal_code',
                            'canonical_value' => 'secret',
                            'display_value' => 'Secret',
                            'is_filterable' => false,
                            'is_sortable' => false,
                            'is_searchable' => false,
                        ],
                        [
                            'code' => 'nullable_filter',
                            'canonical_value' => null,
                            'is_filterable' => true,
                            'is_sortable' => false,
                            'is_searchable' => false,
                        ],
                    ],
                ]],
                'media' => ['main' => ['url' => '/monitor.jpg']],
            ],
            seo: ['canonical_url' => 'https://example.test/products/lg-ultragear-27gp850-b'],
            media: ['main' => ['url' => '/monitor.jpg']],
            checksum: 'product-checksum',
        );

        $builder = app(SearchDocumentBuilder::class);
        $first = $builder->fromProductProjection($projection);
        $second = $builder->fromProductProjection($projection);

        $this->assertStringContainsString('LG UltraGear 27GP850-B', $first->searchText);
        $this->assertStringContainsString('LG', $first->searchText);
        $this->assertStringContainsString('Monitors', $first->searchText);
        $this->assertStringContainsString('165 Hz', $first->searchText);
        $this->assertSame(10, $first->filterValues['brand_id']);
        $this->assertSame(20, $first->filterValues['category_id']);
        $this->assertSame(165, $first->filterValues['refresh_rate']);
        $this->assertArrayNotHasKey('internal_code', $first->filterValues);
        $this->assertArrayNotHasKey('nullable_filter', $first->filterValues);
        $this->assertSame(165, $first->sortValues['refresh_rate']);
        $this->assertSame($first->checksum, $second->checksum);
    }

    public function test_it_builds_a_category_search_document(): void
    {
        $projection = new CategoryProjectionData(
            siteId: 1,
            locale: 'en',
            centralCategoryId: 20,
            parentCategoryId: 5,
            slug: 'monitors',
            title: 'Monitors',
            status: ProjectionStatus::Active,
            payload: [
                'category' => ['id' => 20, 'title' => 'Monitors'],
                'parent' => ['id' => 5, 'title' => 'Electronics'],
                'children' => [['id' => 21, 'title' => 'Gaming Monitors']],
            ],
            seo: [],
            facets: [],
            comparison: [],
            checksum: 'category-checksum',
        );

        $document = app(SearchDocumentBuilder::class)->fromCategoryProjection($projection);

        $this->assertSame('category', $document->documentType);
        $this->assertStringContainsString('Electronics', $document->searchText);
        $this->assertStringContainsString('Gaming Monitors', $document->searchText);
        $this->assertSame(20, $document->filterValues['category_id']);
        $this->assertSame(5, $document->filterValues['parent_category_id']);
    }
}
