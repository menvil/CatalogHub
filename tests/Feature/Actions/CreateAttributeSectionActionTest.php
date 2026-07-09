<?php

namespace Tests\Feature\Actions;

use App\Actions\CategorySchema\CreateAttributeSectionAction;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateAttributeSectionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_attribute_section_for_category(): void
    {
        $category = CentralCategory::factory()->create();

        $section = app(CreateAttributeSectionAction::class)->handle($category, [
            'name' => 'Display',
            'code' => 'display',
            'display_style' => 'table',
            'is_visible' => true,
        ]);

        $this->assertTrue($section->category->is($category));
        $this->assertSame('display', $section->code);
        $this->assertSame('Display', $section->name);
        $this->assertDatabaseHas('attribute_sections', [
            'central_category_id' => $category->id,
            'code' => 'display',
            'name' => 'Display',
            'position' => 1,
        ]);
    }

    public function test_rejects_duplicate_section_code_inside_category(): void
    {
        $category = CentralCategory::factory()->create();
        AttributeSection::factory()->for($category, 'category')->create(['code' => 'display']);

        $this->expectException(ValidationException::class);

        app(CreateAttributeSectionAction::class)->handle($category, [
            'name' => 'Display',
            'code' => 'display',
        ]);
    }

    public function test_rejects_invalid_section_code(): void
    {
        $category = CentralCategory::factory()->create();

        $this->expectException(ValidationException::class);

        app(CreateAttributeSectionAction::class)->handle($category, [
            'name' => 'Display',
            'code' => 'Display Name',
        ]);
    }
}
