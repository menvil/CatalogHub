<?php

namespace Tests\Feature\Sites;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Services\Sites\SiteBrandVisibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandVisibilityRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_can_be_hidden_and_allowed_without_mutating_central_brand(): void
    {
        $site = Site::factory()->create();
        $brand = CentralBrand::factory()->create();
        $name = $brand->name;
        $service = app(SiteBrandVisibilityService::class);
        $service->hide($site, $brand);

        $this->assertFalse($service->allows($site->fresh(), $brand));
        $this->assertSame($name, $brand->fresh()->name);

        $service->allow($site->fresh(), $brand);
        $this->assertTrue($service->allows($site->fresh(), $brand));
    }

    public function test_product_visibility_respects_hidden_brand(): void
    {
        $site = Site::factory()->create();
        $brand = CentralBrand::factory()->create();
        $product = CentralProduct::factory()->create(['central_brand_id' => $brand->id]);
        app(SiteBrandVisibilityService::class)->hide($site, $brand);

        $this->assertFalse(app(SiteBrandVisibilityService::class)->allowsProduct($site->fresh(), $product));
    }

    public function test_hide_uses_current_locked_settings_instead_of_stale_model_state(): void
    {
        $staleSite = Site::factory()->create();
        $existingHiddenBrand = CentralBrand::factory()->create();
        $newHiddenBrand = CentralBrand::factory()->create();
        Site::query()->findOrFail($staleSite->id)->update([
            'settings_json' => ['hidden_brand_ids' => [$existingHiddenBrand->id]],
        ]);

        $service = app(SiteBrandVisibilityService::class);
        $service->hide($staleSite, $newHiddenBrand);
        $freshSite = $staleSite->fresh();

        $this->assertFalse($service->allows($freshSite, $existingHiddenBrand));
        $this->assertFalse($service->allows($freshSite, $newHiddenBrand));
    }
}
