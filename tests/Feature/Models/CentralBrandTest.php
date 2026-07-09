<?php

namespace Tests\Feature\Models;

use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentralBrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_central_brand(): void
    {
        $brand = CentralBrand::factory()->create([
            'name' => 'LG',
            'slug' => 'lg',
        ]);

        $this->assertTrue($brand->exists);
        $this->assertSame('LG', $brand->name);
        $this->assertSame('lg', $brand->slug);
    }

    public function test_casts_central_brand_status_to_enum(): void
    {
        $brand = CentralBrand::factory()->create([
            'status' => CentralBrandStatus::Draft,
        ]);

        $this->assertSame(CentralBrandStatus::Draft, $brand->status);
    }
}
