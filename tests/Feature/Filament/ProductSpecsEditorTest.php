<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\CentralProductResource;
use App\Filament\Resources\CentralProductResource\Pages\ProductSpecsEditor;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSpecsEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_product_resource_registers_specs_editor_page(): void
    {
        $pages = CentralProductResource::getPages();

        $this->assertArrayHasKey('specs', $pages);
        $this->assertTrue(class_exists(ProductSpecsEditor::class));
    }

    public function test_guest_is_redirected_from_product_specs_editor(): void
    {
        $product = CentralProduct::factory()->create();

        $this->get(ProductSpecsEditor::getUrl(['record' => $product]))
            ->assertRedirect('/admin/login');
    }

    public function test_allows_central_admin_to_open_product_specs_editor(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $category = CentralCategory::factory()->create(['name' => 'Monitors']);
        $product = CentralProduct::factory()->for($category, 'category')->create([
            'name' => 'LG UltraGear 27GP850-B',
        ]);

        $this->actingAs($admin)
            ->get(ProductSpecsEditor::getUrl(['record' => $product]))
            ->assertOk()
            ->assertSee('Product Specs')
            ->assertSee('LG UltraGear 27GP850-B')
            ->assertSee('Monitors');
    }

    public function test_product_specs_editor_shows_empty_state_without_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $product = CentralProduct::factory()->create(['name' => 'Uncategorized product']);

        $this->actingAs($admin)
            ->get(ProductSpecsEditor::getUrl(['record' => $product]))
            ->assertOk()
            ->assertSee('Choose a category first')
            ->assertSee('Uncategorized product');
    }
}
