<?php

namespace Tests\Feature\Actions;

use App\Actions\CategorySchema\ExportCategorySchemaAction;
use App\Enums\AttributeDataType;
use App\Enums\CategorySchemaStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportCategorySchemaActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_category_schema_as_deterministic_array(): void
    {
        $category = CentralCategory::factory()->create([
            'slug' => 'monitors',
            'name' => 'Monitors',
            'schema_status' => CategorySchemaStatus::Approved,
        ]);
        $section = AttributeSection::factory()->for($category, 'category')->create([
            'code' => 'display',
            'name' => 'Display',
            'position' => 1,
            'display_style' => 'table',
        ]);
        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'code' => 'refresh_rate',
                'name' => 'Refresh rate',
                'data_type' => AttributeDataType::Integer,
                'dimension' => 'frequency',
                'canonical_unit' => 'hertz',
                'position' => 1,
                'is_filterable' => true,
                'is_sortable' => true,
                'is_comparable' => true,
                'is_searchable' => true,
            ]);
        AttributeOption::factory()->for($attribute, 'attribute')->create([
            'code' => 'fast',
            'label' => 'Fast',
            'position' => 1,
        ]);

        $export = app(ExportCategorySchemaAction::class)->handle($category);

        $this->assertSame('monitors', $export['category']['slug']);
        $this->assertSame('Monitors', $export['category']['name']);
        $this->assertSame('approved', $export['category']['schema_status']);
        $this->assertSame('display', $export['sections'][0]['code']);
        $this->assertSame('refresh_rate', $export['sections'][0]['attributes'][0]['code']);
        $this->assertSame('integer', $export['sections'][0]['attributes'][0]['data_type']);
        $this->assertSame('frequency', $export['sections'][0]['attributes'][0]['dimension']);
        $this->assertTrue($export['sections'][0]['attributes'][0]['flags']['filterable']);
        $this->assertSame('fast', $export['sections'][0]['attributes'][0]['options'][0]['code']);
    }
}
