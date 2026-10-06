<?php

namespace Tests\Feature\Actions;

use App\Actions\CategorySchema\CreateAttributeSectionAction;
use App\Actions\CategorySchema\DeleteAttributeSectionAction;
use App\Exceptions\CategorySchema\CannotDeleteAttributeSectionException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeleteAttributeSectionActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->centralAdmin()->create());
    }

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
            ->assignedTo($section->category)
            ->state(['attribute_section_id' => $section->id])
            ->create();

        $this->expectException(CannotDeleteAttributeSectionException::class);

        app(DeleteAttributeSectionAction::class)->handle($section);
    }

    public function test_nested_section_creation_is_rejected_before_deletion_is_needed(): void
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $this->expectException(ValidationException::class);
        app(CreateAttributeSectionAction::class)->handle($category, ['name' => 'Nested', 'code' => 'nested', 'parent_id' => $section->id]);
    }
}
