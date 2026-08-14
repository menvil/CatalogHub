<?php

namespace Tests\Feature\Models;

use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        foreach (CentralBrandStatus::cases() as $status) {
            $brand = CentralBrand::factory()->create(['status' => $status]);

            $this->assertSame($status, $brand->fresh()->status);
        }
    }

    public function test_central_brand_status_defaults_to_draft(): void
    {
        DB::table('central_brands')->insert([
            'name' => 'LG',
            'normalized_name' => 'lg',
            'normalized_name_hash' => hash('sha256', 'lg'),
            'slug' => 'lg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(CentralBrandStatus::Draft, CentralBrand::first()->status);
    }

    public function test_central_brand_factory_generates_unique_slugs(): void
    {
        $brands = CentralBrand::factory()->count(25)->create();

        $this->assertCount(25, $brands->pluck('slug')->unique());
    }

    public function test_central_brand_factory_defaults_to_draft(): void
    {
        $brand = CentralBrand::factory()->create();

        $this->assertSame(CentralBrandStatus::Draft, $brand->status);
    }

    public function test_central_brand_factory_lifecycle_states(): void
    {
        $draft = CentralBrand::factory()->draft()->create();
        $active = CentralBrand::factory()->active()->create();
        $archived = CentralBrand::factory()->archived()->create();

        $this->assertSame(CentralBrandStatus::Draft, $draft->status);
        $this->assertSame(CentralBrandStatus::Active, $active->status);
        $this->assertSame(CentralBrandStatus::Archived, $archived->status);
    }

    public function test_draft_scope_returns_only_draft_brands(): void
    {
        $draft = CentralBrand::factory()->draft()->create();
        CentralBrand::factory()->active()->create();
        CentralBrand::factory()->archived()->create();

        $this->assertSame([$draft->id], CentralBrand::query()->draft()->pluck('id')->all());
    }

    public function test_active_scope_returns_only_active_brands(): void
    {
        CentralBrand::factory()->draft()->create();
        $active = CentralBrand::factory()->active()->create();
        CentralBrand::factory()->archived()->create();

        $this->assertSame([$active->id], CentralBrand::query()->active()->pluck('id')->all());
    }

    public function test_archived_scope_returns_only_archived_brands(): void
    {
        CentralBrand::factory()->draft()->create();
        CentralBrand::factory()->active()->create();
        $archived = CentralBrand::factory()->archived()->create();

        $this->assertSame([$archived->id], CentralBrand::query()->archived()->pluck('id')->all());
    }

    public function test_canonical_metadata_persists(): void
    {
        $brand = CentralBrand::factory()->create([
            'name' => 'Logitech',
            'slug' => 'logitech',
            'status' => CentralBrandStatus::Active,
            'website_url' => 'https://www.logitech.com',
            'country_code' => 'CH',
        ]);

        $brand->refresh();

        $this->assertSame('https://www.logitech.com', $brand->website_url);
        $this->assertSame('CH', $brand->country_code);
    }

    public function test_canonical_metadata_is_nullable(): void
    {
        $brand = CentralBrand::factory()->create();

        $this->assertNull($brand->website_url);
        $this->assertNull($brand->country_code);
    }
}
