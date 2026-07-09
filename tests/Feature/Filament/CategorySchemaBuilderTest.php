<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\CentralCategoryResource;
use App\Filament\Resources\CentralCategoryResource\Pages\CategorySchemaBuilder;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySchemaBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_category_resource_registers_schema_builder_page(): void
    {
        $pages = CentralCategoryResource::getPages();

        $this->assertArrayHasKey('schema', $pages);
        $this->assertTrue(class_exists(CategorySchemaBuilder::class));
    }

    public function test_guest_is_redirected_from_category_schema_builder(): void
    {
        $category = CentralCategory::factory()->create();

        $this->get(CategorySchemaBuilder::getUrl(['record' => $category]))
            ->assertRedirect('/admin/login');
    }

    public function test_allows_central_admin_to_open_category_schema_builder(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $category = CentralCategory::factory()->create(['name' => 'Monitors']);

        $this->actingAs($admin)
            ->get(CategorySchemaBuilder::getUrl(['record' => $category]))
            ->assertOk()
            ->assertSee('Category Schema Builder')
            ->assertSee('Monitors')
            ->assertSee('No attribute sections yet');
    }

    public function test_category_schema_builder_shows_sections_and_attributes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $category = CentralCategory::factory()->create(['name' => 'Monitors']);
        $section = AttributeSection::factory()->for($category, 'category')->create([
            'name' => 'Display',
            'code' => 'display',
            'position' => 1,
        ]);

        AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'name' => 'Refresh rate',
                'code' => 'refresh_rate',
                'data_type' => 'integer',
                'position' => 1,
            ]);

        $this->actingAs($admin)
            ->get(CategorySchemaBuilder::getUrl(['record' => $category]))
            ->assertOk()
            ->assertSee('Display')
            ->assertSee('display')
            ->assertSee('Refresh rate')
            ->assertSee('refresh_rate')
            ->assertSee('integer');
    }
}
