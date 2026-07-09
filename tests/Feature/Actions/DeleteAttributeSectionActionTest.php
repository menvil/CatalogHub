<?php

namespace Tests\Feature\Actions;

use App\Actions\CategorySchema\DeleteAttributeSectionAction;
use App\Exceptions\CategorySchema\CannotDeleteAttributeSectionException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteAttributeSectionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_empty_attribute_section(): void
    {
        $section = AttributeSection::factory()->create();

        app(DeleteAttributeSectionAction::class)->handle($section);

        $this->assertDatabaseMissing('attribute_sections', ['id' => $section->id]);
    }

    public function test_does_not_delete_section_with_attributes(): void
    {
        $section = AttributeSection::factory()->create();
        AttributeDefinition::factory()
            ->for($section->category, 'category')
            ->for($section, 'section')
            ->create();

        $this->expectException(CannotDeleteAttributeSectionException::class);

        app(DeleteAttributeSectionAction::class)->handle($section);
    }

    public function test_does_not_delete_section_with_child_sections(): void
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        AttributeSection::factory()
            ->for($category, 'category')
            ->for($section, 'parent')
            ->create();

        $this->expectException(CannotDeleteAttributeSectionException::class);

        app(DeleteAttributeSectionAction::class)->handle($section);
    }
}
