<?php

namespace Tests\Feature\Sites;

use App\Actions\Sites\UpdateSiteProductVisibilityAction;
use App\Enums\CentralProductStatus;
use App\Enums\UserRole;
use App\Filament\Resources\SiteResource\Pages\ManageSiteProducts;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ProductVisibilityManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_visibility_and_featured_state_are_local_to_site(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        DB::table('site_categories')->insert(['site_id' => $site->id, 'central_category_id' => $category->id, 'is_enabled' => true, 'position' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $product = CentralProduct::factory()->create(['central_category_id' => $category->id, 'status' => CentralProductStatus::Active]);
        $original = $product->only(['name', 'model', 'slug', 'status', 'central_category_id']);

        app(UpdateSiteProductVisibilityAction::class)->handle($site, $product, 'visible', true);

        $this->assertDatabaseHas('site_products', ['site_id' => $site->id, 'central_product_id' => $product->id, 'visibility' => 'visible', 'is_featured' => true]);
        $this->assertSame($original, $product->fresh()->only(array_keys($original)));
    }

    public function test_product_outside_enabled_categories_is_rejected(): void
    {
        try {
            app(UpdateSiteProductVisibilityAction::class)->handle(
                Site::factory()->create(),
                CentralProduct::factory()->create([
                    'central_category_id' => CentralCategory::factory()->create()->id,
                    'status' => CentralProductStatus::Active,
                ]),
                'visible',
            );

            $this->fail('A product outside the enabled categories was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('product', $exception->errors());
            $this->assertSame('The product category is not enabled for this site.', $exception->errors()['product'][0]);
        }

        $this->assertDatabaseCount('site_products', 0);
    }

    public function test_toggle_featured_creates_hidden_row_when_no_visibility_row_exists(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        DB::table('site_categories')->insert(['site_id' => $site->id, 'central_category_id' => $category->id, 'is_enabled' => true, 'position' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $product = CentralProduct::factory()->create(['central_category_id' => $category->id, 'status' => CentralProductStatus::Active]);

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(ManageSiteProducts::class, ['record' => $site->getRouteKey()])
            ->call('toggleFeatured', $product->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('site_products', [
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'visibility' => 'hidden',
            'is_featured' => true,
        ]);
    }

    public function test_translator_cannot_access_product_visibility_manager(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->create(['role' => UserRole::Translator]))
            ->get(ManageSiteProducts::getUrl(['record' => $site]))
            ->assertForbidden();
    }

    public function test_repeated_visibility_updates_update_one_site_product_row(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        DB::table('site_categories')->insert(['site_id' => $site->id, 'central_category_id' => $category->id, 'is_enabled' => true, 'position' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $product = CentralProduct::factory()->create(['central_category_id' => $category->id, 'status' => CentralProductStatus::Active]);
        $action = app(UpdateSiteProductVisibilityAction::class);

        $action->handle($site, $product, 'visible');
        $action->handle($site, $product, 'excluded', true);

        $this->assertDatabaseCount('site_products', 1);
        $this->assertDatabaseHas('site_products', [
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'visibility' => 'excluded',
            'is_featured' => true,
        ]);
    }

    public function test_visibility_change_preserves_featured_state_inside_the_locked_update(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        DB::table('site_categories')->insert(['site_id' => $site->id, 'central_category_id' => $category->id, 'is_enabled' => true, 'position' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $product = CentralProduct::factory()->create(['central_category_id' => $category->id, 'status' => CentralProductStatus::Active]);
        $action = app(UpdateSiteProductVisibilityAction::class);

        $action->handle($site, $product, 'visible', true);
        $action->handle($site, $product, 'hidden');

        $this->assertDatabaseHas('site_products', [
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'visibility' => 'hidden',
            'is_featured' => true,
        ]);
    }

    public function test_featured_toggle_reads_and_inverts_state_inside_the_locked_update(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        DB::table('site_categories')->insert(['site_id' => $site->id, 'central_category_id' => $category->id, 'is_enabled' => true, 'position' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $product = CentralProduct::factory()->create(['central_category_id' => $category->id, 'status' => CentralProductStatus::Active]);
        $action = app(UpdateSiteProductVisibilityAction::class);

        $action->toggleFeatured($site, $product);
        $action->toggleFeatured($site, $product);

        $this->assertDatabaseHas('site_products', [
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'visibility' => 'hidden',
            'is_featured' => false,
        ]);
    }

    public function test_inactive_product_cannot_be_added_to_a_site(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        DB::table('site_categories')->insert(['site_id' => $site->id, 'central_category_id' => $category->id, 'is_enabled' => true, 'position' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $product = CentralProduct::factory()->create([
            'central_category_id' => $category->id,
            'status' => CentralProductStatus::Archived,
        ]);

        try {
            app(UpdateSiteProductVisibilityAction::class)->handle($site, $product, 'visible');

            $this->fail('An inactive product was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('product', $exception->errors());
        }

        $this->assertDatabaseCount('site_products', 0);
    }

    public function test_product_manager_lists_only_active_products(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        DB::table('site_categories')->insert(['site_id' => $site->id, 'central_category_id' => $category->id, 'is_enabled' => true, 'position' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $active = CentralProduct::factory()->create(['central_category_id' => $category->id, 'status' => CentralProductStatus::Active]);
        $archived = CentralProduct::factory()->create(['central_category_id' => $category->id, 'status' => CentralProductStatus::Archived]);

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(ManageSiteProducts::class, ['record' => $site->getRouteKey()])
            ->assertSee($active->name)
            ->assertDontSee($archived->name);
    }

    public function test_product_manager_paginates_and_searches_large_catalogs(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        DB::table('site_categories')->insert(['site_id' => $site->id, 'central_category_id' => $category->id, 'is_enabled' => true, 'position' => 0, 'created_at' => now(), 'updated_at' => now()]);

        foreach (range(1, 51) as $index) {
            CentralProduct::factory()->create([
                'central_category_id' => $category->id,
                'status' => CentralProductStatus::Active,
                'name' => sprintf('Paginated Product %02d', $index),
            ]);
        }

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(ManageSiteProducts::class, ['record' => $site->getRouteKey()])
            ->assertSee('Paginated Product 01')
            ->assertDontSee('Paginated Product 51')
            ->set('search', 'paginated product 51')
            ->assertSee('Paginated Product 51');
    }
}
