<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\CentralProductResource;
use App\Filament\Resources\CentralProductResource\Pages\ViewCentralProduct;
use Tests\TestCase;

class CentralProductDetailPageTest extends TestCase
{
    public function test_central_product_resource_has_detail_page(): void
    {
        $pages = CentralProductResource::getPages();

        $this->assertArrayHasKey('view', $pages);
        $this->assertTrue(class_exists(ViewCentralProduct::class));
    }
}
