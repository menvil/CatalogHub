<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\CentralBrandResource;
use App\Filament\Resources\CentralBrandResource\Pages\CreateCentralBrand;
use App\Filament\Resources\CentralBrandResource\Pages\EditCentralBrand;
use App\Filament\Resources\CentralBrandResource\Pages\ListCentralBrands;
use App\Models\CentralCatalog\CentralBrand;
use Tests\TestCase;

class CentralBrandResourceTest extends TestCase
{
    public function test_has_central_brand_filament_resource(): void
    {
        $this->assertTrue(class_exists(CentralBrandResource::class));
        $this->assertSame(CentralBrand::class, CentralBrandResource::getModel());
    }

    public function test_central_brand_resource_has_expected_pages(): void
    {
        $pages = CentralBrandResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
        $this->assertTrue(class_exists(ListCentralBrands::class));
        $this->assertTrue(class_exists(CreateCentralBrand::class));
        $this->assertTrue(class_exists(EditCentralBrand::class));
    }
}
