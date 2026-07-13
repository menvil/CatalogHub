<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\AttributeMappingResource\Pages\CreateAttributeMapping;
use App\Filament\Resources\AttributeMappingResource\Pages\EditAttributeMapping;
use App\Filament\Resources\AttributeMappingResource\Pages\ListAttributeMappings;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Imports\AttributeMapping;
use App\Models\Imports\ImportSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MappingRulesEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_reviewed_source_category_mapping(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $source = ImportSource::factory()->create();
        $category = CentralCategory::factory()->create();
        $definition = AttributeDefinition::factory()->for($category, 'category')->create();

        Livewire::actingAs($admin)
            ->test(CreateAttributeMapping::class)
            ->fillForm([
                'import_source_id' => $source->id,
                'category_id' => $category->id,
                'raw_key' => 'Power (W)',
                'normalized_raw_key' => 'power_w',
                'attribute_definition_id' => $definition->id,
                'confidence' => '1.0000',
                'status' => 'reviewed',
                'mapping_type' => 'attribute',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('attribute_mappings', [
            'import_source_id' => $source->id,
            'category_id' => $category->id,
            'raw_key' => 'Power (W)',
            'normalized_raw_key' => 'power_w',
            'attribute_definition_id' => $definition->id,
            'status' => 'reviewed',
        ]);
    }

    public function test_can_edit_and_reject_mapping(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $mapping = $this->mapping();

        Livewire::actingAs($admin)
            ->test(EditAttributeMapping::class, ['record' => $mapping->getRouteKey()])
            ->fillForm(['status' => 'rejected'])
            ->set('data.notes', 'Wrong source field')
            ->assertSet('data.notes', 'Wrong source field')
            ->call('save')
            ->assertHasNoFormErrors();

        $mapping = $mapping->fresh();
        $this->assertSame('rejected', $mapping->status);
        $this->assertSame('Wrong source field', $mapping->notes);
    }

    public function test_list_filters_by_source_and_category(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $visible = $this->mapping();
        $other = $this->mapping();

        Livewire::actingAs($admin)
            ->test(ListAttributeMappings::class)
            ->filterTable('source', $visible->import_source_id)
            ->filterTable('category', $visible->category_id)
            ->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_changing_category_clears_the_selected_attribute_definition(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $mapping = $this->mapping();
        $newCategory = CentralCategory::factory()->create();

        Livewire::actingAs($admin)
            ->test(EditAttributeMapping::class, ['record' => $mapping->getRouteKey()])
            ->assertSet('data.attribute_definition_id', $mapping->attribute_definition_id)
            ->set('data.category_id', $newCategory->id)
            ->assertSet('data.attribute_definition_id', null);
    }

    private function mapping(): AttributeMapping
    {
        $source = ImportSource::factory()->create();
        $category = CentralCategory::factory()->create();
        $definition = AttributeDefinition::factory()->for($category, 'category')->create();

        return AttributeMapping::query()->create([
            'import_source_id' => $source->id,
            'category_id' => $category->id,
            'raw_key' => fake()->unique()->word(),
            'normalized_raw_key' => fake()->unique()->word(),
            'attribute_definition_id' => $definition->id,
            'confidence' => '1.0000',
            'status' => 'reviewed',
            'mapping_type' => 'attribute',
        ]);
    }
}
