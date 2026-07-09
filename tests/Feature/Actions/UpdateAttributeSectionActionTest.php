<?php

namespace Tests\Feature\Actions;

use App\Actions\CategorySchema\UpdateAttributeSectionAction;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateAttributeSectionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_attribute_section(): void
    {
        $section = AttributeSection::factory()->create([
            'name' => 'Old',
            'code' => 'old',
            'is_visible' => false,
        ]);

        app(UpdateAttributeSectionAction::class)->handle($section, [
            'name' => 'Display',
            'code' => 'display',
            'position' => 3,
            'display_style' => 'list',
            'is_collapsible' => false,
            'is_visible' => true,
        ]);

        $section->refresh();

        $this->assertSame('Display', $section->name);
        $this->assertSame('display', $section->code);
        $this->assertSame(3, $section->position);
        $this->assertSame('list', $section->display_style);
        $this->assertFalse($section->is_collapsible);
        $this->assertTrue($section->is_visible);
    }

    public function test_allows_keeping_current_section_code(): void
    {
        $section = AttributeSection::factory()->create(['code' => 'display']);

        app(UpdateAttributeSectionAction::class)->handle($section, [
            'name' => 'Display specs',
            'code' => 'display',
        ]);

        $section->refresh();

        $this->assertSame('Display specs', $section->name);
        $this->assertSame('display', $section->code);
    }

    public function test_rejects_duplicate_section_code_inside_category(): void
    {
        $category = CentralCategory::factory()->create();
        AttributeSection::factory()->for($category, 'category')->create(['code' => 'display']);
        $section = AttributeSection::factory()->for($category, 'category')->create(['code' => 'ports']);

        $this->expectException(ValidationException::class);

        app(UpdateAttributeSectionAction::class)->handle($section, [
            'name' => 'Ports',
            'code' => 'display',
        ]);
    }

    public function test_rejects_unsupported_display_style(): void
    {
        $section = AttributeSection::factory()->create();

        $this->expectException(ValidationException::class);

        app(UpdateAttributeSectionAction::class)->handle($section, [
            'name' => 'Display',
            'code' => 'display',
            'display_style' => 'grid',
        ]);
    }
}
