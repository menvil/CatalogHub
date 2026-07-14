<?php

namespace Tests\Unit\Domains\Projections;

use App\Domains\Projections\Builders\ProductProjectionBuilder;
use App\Domains\Projections\DTO\ProductProjectionData;
use ReflectionMethod;
use Tests\TestCase;

class ProductProjectionBuilderSkeletonTest extends TestCase
{
    public function test_builder_resolves_with_the_expected_build_contract(): void
    {
        $builder = app(ProductProjectionBuilder::class);
        $method = new ReflectionMethod($builder, 'build');

        $this->assertSame('build', $method->getName());
        $this->assertSame(3, $method->getNumberOfParameters());
        $this->assertSame(ProductProjectionData::class, (string) $method->getReturnType());
    }

    public function test_product_projection_data_exposes_the_minimum_read_model_fields(): void
    {
        $projection = new ProductProjectionData(
            siteId: 1,
            locale: 'en',
            centralProductId: 2,
            slug: null,
            title: null,
            status: 'pending',
            payload: [],
            seo: [],
            media: [],
            checksum: null,
        );

        $this->assertSame(1, $projection->siteId);
        $this->assertSame('en', $projection->locale);
        $this->assertSame(2, $projection->centralProductId);
        $this->assertSame('pending', $projection->status);
    }
}
