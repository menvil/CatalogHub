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

    public function test_table_groups_facets_by_category_without_global_reordering(): void
    {
        $component = Livewire::actingAs(User::factory()->create(['role' => UserRole::CatalogEditor]))
            ->test(ListFacetDefinitions::class);
        $instance = $component->instance();

        if (! $instance instanceof ListFacetDefinitions) {
            $this->fail('Expected the facet definitions list page.');
        }

        $table = $instance->getTable();

        $this->assertSame('category.name', $table->getDefaultGroup()?->getId());
        $this->assertFalse($table->isReorderable());
    }

    public function test_code_must_be_unique_within_selected_category(): void
    {
        $category = CentralCategory::factory()->create();
        FacetDefinition::factory()->for($category, 'category')->create(['code' => 'brand']);

        Livewire::actingAs(User::factory()->create(['role' => UserRole::CatalogEditor]))
            ->test(CreateFacetDefinition::class)
            ->fillForm([
                'category_id' => $category->id,
                'source_type' => FacetSourceType::Brand->value,
                'code' => 'brand',
                'facet_type' => FacetType::Checkbox->value,
                'position' => 20,
            ])
            ->call('create')
            ->assertHasFormErrors(['code' => 'unique']);

        $this->assertSame(1, FacetDefinition::query()->where('category_id', $category->id)->count());
    }

    public function test_code_can_be_reused_in_another_category_and_kept_during_edit(): void
    {
        $firstCategory = CentralCategory::factory()->create();
        $secondCategory = CentralCategory::factory()->create();
        $facet = FacetDefinition::factory()->for($firstCategory, 'category')->create(['code' => 'brand']);

        Livewire::actingAs(User::factory()->create(['role' => UserRole::CatalogEditor]))
            ->test(CreateFacetDefinition::class)
            ->fillForm([
                'category_id' => $secondCategory->id,
                'source_type' => FacetSourceType::Brand->value,
                'code' => 'brand',
                'facet_type' => FacetType::Checkbox->value,
                'position' => 10,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::actingAs(User::factory()->create(['role' => UserRole::CatalogEditor]))
            ->test(EditFacetDefinition::class, ['record' => $facet->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();
    }
}
