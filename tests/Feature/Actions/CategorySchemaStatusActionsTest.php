<?php

namespace Tests\Feature\Actions;

use App\Actions\CategorySchema\ApproveCategorySchemaAction;
use App\Actions\CategorySchema\ArchiveCategorySchemaAction;
use App\Actions\CategorySchema\MarkCategorySchemaReviewedAction;
use App\Enums\AttributeDataType;
use App\Enums\CategorySchemaStatus;
use App\Exceptions\CategorySchema\CannotApproveCategorySchemaException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySchemaStatusActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_category_schema_status_to_enum(): void
    {
        $category = CentralCategory::factory()->create([
            'schema_status' => CategorySchemaStatus::Draft,
        ]);

        $this->assertSame(CategorySchemaStatus::Draft, $category->schema_status);
    }

    public function test_marks_category_schema_reviewed(): void
    {
        $category = CentralCategory::factory()->create([
            'schema_status' => CategorySchemaStatus::Draft,
        ]);

        app(MarkCategorySchemaReviewedAction::class)->handle($category);

        $this->assertSame(CategorySchemaStatus::Reviewed, $category->fresh()->schema_status);
    }

    public function test_approves_valid_category_schema(): void
    {
        $category = CentralCategory::factory()->create([
            'schema_status' => CategorySchemaStatus::Reviewed,
        ]);

        app(ApproveCategorySchemaAction::class)->handle($category);

        $this->assertSame(CategorySchemaStatus::Approved, $category->fresh()->schema_status);
    }

    public function test_does_not_approve_schema_with_validation_errors(): void
    {
        $category = CentralCategory::factory()->create([
            'schema_status' => CategorySchemaStatus::Reviewed,
        ]);
        $attribute = AttributeDefinition::factory()->for($category, 'category')->create([
            'data_type' => AttributeDataType::Decimal,
        ]);
        AttributeOption::factory()->for($attribute, 'attribute')->create();

        $this->expectException(CannotApproveCategorySchemaException::class);

        app(ApproveCategorySchemaAction::class)->handle($category);
    }

    public function test_archives_category_schema(): void
    {
        $category = CentralCategory::factory()->create([
            'schema_status' => CategorySchemaStatus::Approved,
        ]);

        app(ArchiveCategorySchemaAction::class)->handle($category);

        $this->assertSame(CategorySchemaStatus::Archived, $category->fresh()->schema_status);
    }
}
