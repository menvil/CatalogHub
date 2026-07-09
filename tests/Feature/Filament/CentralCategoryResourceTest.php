<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\CentralCategoryResource;
use App\Filament\Resources\CentralCategoryResource\Pages\CreateCentralCategory;
use App\Filament\Resources\CentralCategoryResource\Pages\EditCentralCategory;
use App\Filament\Resources\CentralCategoryResource\Pages\ListCentralCategories;
use App\Models\CentralCatalog\CentralCategory;
use Tests\TestCase;

class CentralCategoryResourceTest extends TestCase
{
    public function test_has_central_category_filament_resource(): void
    {
        $this->assertTrue(class_exists(CentralCategoryResource::class));
        $this->assertSame(CentralCategory::class, CentralCategoryResource::getModel());
    }

    public function test_central_category_resource_has_expected_pages(): void
    {
        $pages = CentralCategoryResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
        $this->assertTrue(class_exists(ListCentralCategories::class));
        $this->assertTrue(class_exists(CreateCentralCategory::class));
        $this->assertTrue(class_exists(EditCentralCategory::class));
    }
}
