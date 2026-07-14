<?php

namespace Tests\Feature\Filament;

use App\Enums\FacetSourceType;
use App\Enums\FacetType;
use App\Enums\UserRole;
use App\Filament\Resources\FacetDefinitionResource;
use App\Filament\Resources\FacetDefinitionResource\Pages\CreateFacetDefinition;
use App\Filament\Resources\FacetDefinitionResource\Pages\EditFacetDefinition;
use App\Filament\Resources\FacetDefinitionResource\Pages\ListFacetDefinitions;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FacetDefinitionResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_exposes_index_create_and_edit_pages(): void
    {
        $this->assertSame(FacetDefinition::class, FacetDefinitionResource::getModel());

        $pages = FacetDefinitionResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
        $this->assertTrue(class_exists(ListFacetDefinitions::class));
        $this->assertTrue(class_exists(CreateFacetDefinition::class));
        $this->assertTrue(class_exists(EditFacetDefinition::class));
    }

    public function test_catalog_editor_can_view_facet_admin_screen(): void
    {
        $user = User::factory()->create(['role' => UserRole::CatalogEditor]);

        $this->actingAs($user)
            ->get(FacetDefinitionResource::getUrl())
            ->assertOk();
    }

    public function test_site_admin_cannot_view_facet_admin_screen(): void
    {
        $user = User::factory()->create(['role' => UserRole::SiteAdmin]);

        $this->actingAs($user)
            ->get(FacetDefinitionResource::getUrl())
            ->assertForbidden();
    }

    public function test_catalog_editor_can_create_facet_definition(): void
    {
        $category = CentralCategory::factory()->create();

        Livewire::actingAs(User::factory()->create(['role' => UserRole::CatalogEditor]))
            ->test(CreateFacetDefinition::class)
            ->fillForm([
                'category_id' => $category->id,
                'source_type' => FacetSourceType::Brand->value,
                'code' => 'brand',
                'facet_type' => FacetType::Checkbox->value,
                'position' => 10,
                'is_active' => true,
                'is_filterable' => true,
                'is_visible' => true,
                'is_collapsible' => true,
                'default_collapsed' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('facet_definitions', [
            'category_id' => $category->id,
            'code' => 'brand',
        ]);
    }
}
