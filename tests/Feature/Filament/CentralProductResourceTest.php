<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\CentralProductResource;
use App\Filament\Resources\CentralProductResource\Pages\CreateCentralProduct;
use App\Filament\Resources\CentralProductResource\Pages\EditCentralProduct;
use App\Filament\Resources\CentralProductResource\Pages\ListCentralProducts;
use App\Filament\Resources\CentralProductResource\Pages\ViewCentralProduct;
use App\Models\CentralCatalog\CentralProduct;
use Tests\TestCase;

class CentralProductResourceTest extends TestCase
{
    public function test_has_central_product_filament_resource(): void
    {
        $this->assertTrue(class_exists(CentralProductResource::class));
        $this->assertSame(CentralProduct::class, CentralProductResource::getModel());
    }

    public function test_central_product_resource_has_expected_pages(): void
    {
        $pages = CentralProductResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('view', $pages);
        $this->assertArrayHasKey('edit', $pages);
        $this->assertTrue(class_exists(ListCentralProducts::class));
        $this->assertTrue(class_exists(CreateCentralProduct::class));
        $this->assertTrue(class_exists(ViewCentralProduct::class));
        $this->assertTrue(class_exists(EditCentralProduct::class));
    }
}
