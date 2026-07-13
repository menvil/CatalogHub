<?php

namespace Tests\Feature\Sites;

use App\Actions\Sites\UpdateSiteProductVisibilityAction;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductVisibilityManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_visibility_and_featured_state_are_local_to_site(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        DB::table('site_categories')->insert(['site_id' => $site->id, 'central_category_id' => $category->id, 'is_enabled' => true, 'position' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $product = CentralProduct::factory()->create(['central_category_id' => $category->id]);
        $original = $product->only(['name', 'model', 'slug', 'status', 'central_category_id']);

        app(UpdateSiteProductVisibilityAction::class)->handle($site, $product, 'visible', true);

        $this->assertDatabaseHas('site_products', ['site_id' => $site->id, 'central_product_id' => $product->id, 'visibility' => 'visible', 'is_featured' => true]);
        $this->assertSame($original, $product->fresh()->only(array_keys($original)));
    }

    public function test_product_outside_enabled_categories_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(UpdateSiteProductVisibilityAction::class)->handle(Site::factory()->create(), CentralProduct::factory()->create(['central_category_id' => CentralCategory::factory()->create()->id]), 'visible');
    }
}
