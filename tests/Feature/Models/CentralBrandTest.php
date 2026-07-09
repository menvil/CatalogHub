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

    public function test_central_brand_status_defaults_to_draft(): void
    {
        CentralBrand::query()->create([
            'name' => 'LG',
            'slug' => 'lg',
        ]);

        $this->assertSame(CentralBrandStatus::Draft, CentralBrand::first()->status);
    }

    public function test_central_brand_factory_generates_unique_slugs(): void
    {
        $brands = CentralBrand::factory()->count(25)->create();

        $this->assertCount(25, $brands->pluck('slug')->unique());
    }
}
