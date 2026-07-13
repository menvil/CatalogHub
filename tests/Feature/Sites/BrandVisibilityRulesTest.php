<?php

namespace Tests\Feature\Sites;

use App\Enums\UserRole;
use App\Filament\Resources\SiteResource\Pages\BrandVisibilityRules;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\User;
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
        $service = app(SiteBrandVisibilityService::class);
        $service->hide($site, $brand);

        $this->assertFalse($service->allowsProduct($site->fresh(), $product));
        $service->allow($site->fresh(), $brand);
        $this->assertTrue($service->allowsProduct($site->fresh(), $product));
    }

    public function test_product_visibility_does_not_lazy_load_the_brand_relation(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create([
            'central_brand_id' => CentralBrand::factory()->create()->id,
        ]);

        $this->assertFalse($product->relationLoaded('brand'));
        app(SiteBrandVisibilityService::class)->allowsProduct($site, $product);
        $this->assertFalse($product->relationLoaded('brand'));
    }

    public function test_brand_rows_have_stable_livewire_keys(): void
    {
        $site = Site::factory()->create();
        $brand = CentralBrand::factory()->create();

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(BrandVisibilityRules::getUrl(['record' => $site]))
            ->assertOk()
            ->assertSee('wire:key="brand-visibility-'.$brand->id.'"', false);
    }

    public function test_catalog_editor_cannot_access_brand_visibility_rules(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->create(['role' => UserRole::CatalogEditor]))
            ->get(BrandVisibilityRules::getUrl(['record' => $site]))
            ->assertForbidden();
    }

    public function test_atomic_toggle_observes_the_latest_visibility_state(): void
    {
        $site = Site::factory()->create();
        $brand = CentralBrand::factory()->create();
        $service = app(SiteBrandVisibilityService::class);

        $service->toggle($site, $brand);
        $this->assertFalse($service->allows($site->fresh(), $brand));

        $service->toggle($site, $brand);
        $this->assertTrue($service->allows($site->fresh(), $brand));
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
