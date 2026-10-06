<?php

namespace Tests\Feature\Actions;

use App\Actions\CategorySchema\ExportCategorySchemaAction;
use App\Enums\AttributeDataType;
use App\Enums\CategorySchemaStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportCategorySchemaActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_category_schema_as_deterministic_array(): void
    {
        $this->actingAs(User::factory()->centralAdmin()->create());
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class]);
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
        $childSection = AttributeSection::factory()
            ->for($category, 'category')
            ->create([
                'code' => 'panel',
                'name' => 'Panel',
                'position' => 2,
            ]);
        $attribute = AttributeDefinition::factory()->measured('frequency', 'hertz')
            ->assignedTo($category)
            ->state(['attribute_section_id' => $childSection->id])
            ->create([
                'code' => 'refresh_rate',
                'name' => 'Refresh rate',
                'data_type' => AttributeDataType::Integer,
                'position' => 1,
                'is_sortable' => true,
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
        self::assertSame(1, $export['schema_version']);
        self::assertSame(['display', 'panel'], array_column($export['sections'], 'code'));
        self::assertSame($attribute->id, $export['definitions'][0]['id']);
        self::assertSame($attribute->measurement_dimension_id, $export['definitions'][0]['measurement_dimension_id']);
        self::assertSame($childSection->id, $export['assignments'][0]['attribute_section_id']);
        self::assertTrue($export['assignments'][0]['is_searchable']);
        self::assertArrayNotHasKey('is_filterable', $export['definitions'][0]);
        self::assertSame($export, app(ExportCategorySchemaAction::class)->handle($category));
    }
}
