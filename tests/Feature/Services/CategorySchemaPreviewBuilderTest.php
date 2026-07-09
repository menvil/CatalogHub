<?php

namespace Tests\Feature\Services;

use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Services\CategorySchema\CategorySchemaPreviewBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySchemaPreviewBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_category_schema_preview_grouped_by_sections(): void
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create([
            'name' => 'Display',
            'code' => 'display',
            'position' => 2,
        ]);
        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'name' => 'Refresh rate',
                'code' => 'refresh_rate',
                'data_type' => AttributeDataType::Integer,
                'position' => 1,
                'is_filterable' => true,
            ]);
        AttributeOption::factory()->for($attribute, 'attribute')->create(['position' => 1]);

        $preview = app(CategorySchemaPreviewBuilder::class)->build($category);

        $this->assertSame('Display', $preview[0]['section']);
        $this->assertSame('display', $preview[0]['code']);
        $this->assertSame('Refresh rate', $preview[0]['attributes'][0]['name']);
        $this->assertSame('integer', $preview[0]['attributes'][0]['data_type']);
        $this->assertTrue($preview[0]['attributes'][0]['flags']['filterable']);
        $this->assertSame(1, $preview[0]['attributes'][0]['options_count']);
    }

    public function test_preview_respects_section_and_attribute_ordering(): void
    {
        $category = CentralCategory::factory()->create();
        $second = AttributeSection::factory()->for($category, 'category')->create(['code' => 'second', 'position' => 2]);
        $first = AttributeSection::factory()->for($category, 'category')->create(['code' => 'first', 'position' => 1]);

        AttributeDefinition::factory()->for($category, 'category')->for($first, 'section')->create(['code' => 'b', 'position' => 2]);
        AttributeDefinition::factory()->for($category, 'category')->for($first, 'section')->create(['code' => 'a', 'position' => 1]);
        AttributeDefinition::factory()->for($category, 'category')->for($second, 'section')->create(['code' => 'z', 'position' => 1]);

        $preview = app(CategorySchemaPreviewBuilder::class)->build($category);

        $this->assertSame(['first', 'second'], array_column($preview, 'code'));
        $this->assertSame(['a', 'b'], array_column($preview[0]['attributes'], 'code'));
    }
}
