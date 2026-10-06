<?php

namespace Tests\Feature\Actions;

use App\Actions\CategorySchema\ApproveCategorySchemaAction;
use App\Actions\CategorySchema\ArchiveCategorySchemaAction;
use App\Actions\CategorySchema\MarkCategorySchemaReviewedAction;
use App\Actions\CategorySchema\RestoreCategorySchemaAction;
use App\Enums\AttributeDataType;
use App\Enums\CategorySchemaStatus;
use App\Exceptions\CategorySchema\CannotApproveCategorySchemaException;
use App\Exceptions\CategorySchema\CannotTransitionCategorySchemaStatusException;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CategorySchemaStatusActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->centralAdmin()->create());
    }

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
            'schema_reviewed_revision' => 1,
        ]);

        app(ApproveCategorySchemaAction::class)->handle($category);

        $this->assertSame(CategorySchemaStatus::Approved, $category->fresh()->schema_status);
    }

    public function test_does_not_approve_schema_with_validation_errors(): void
    {
        $category = CentralCategory::factory()->create([
            'schema_status' => CategorySchemaStatus::Reviewed,
            'schema_reviewed_revision' => 1,
        ]);
        $attribute = AttributeDefinition::factory()->assignedTo($category)->create([
            'data_type' => AttributeDataType::Decimal,
        ]);
        AttributeOption::factory()->for($attribute, 'attribute')->create();

        $this->expectException(CannotApproveCategorySchemaException::class);

        app(ApproveCategorySchemaAction::class)->handle($category);
    }

    public function test_does_not_approve_schema_before_review(): void
    {
        $category = CentralCategory::factory()->create([
            'schema_status' => CategorySchemaStatus::Draft,
        ]);

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

    public function test_does_not_mark_non_draft_schema_reviewed(): void
    {
        $category = CentralCategory::factory()->create([
            'schema_status' => CategorySchemaStatus::Approved,
        ]);

        $this->expectException(CannotTransitionCategorySchemaStatusException::class);

        app(MarkCategorySchemaReviewedAction::class)->handle($category);
    }

    public function test_does_not_archive_non_approved_schema(): void
    {
        $category = CentralCategory::factory()->create([
            'schema_status' => CategorySchemaStatus::Draft,
        ]);

        $this->expectException(CannotTransitionCategorySchemaStatusException::class);

        app(ArchiveCategorySchemaAction::class)->handle($category);
    }

    public function test_restore_rejects_non_archived_schema_with_a_lifecycle_exception(): void
    {
        $category = CentralCategory::factory()->create([
            'schema_status' => CategorySchemaStatus::Reviewed,
            'schema_reviewed_revision' => 1,
        ]);

        try {
            app(RestoreCategorySchemaAction::class)->handle($category);
            self::fail('Non-archived schema was restored.');
        } catch (CannotTransitionCategorySchemaStatusException $exception) {
            self::assertSame('Only archived category schemas can be restored.', $exception->getMessage());
        }

        self::assertSame(CategorySchemaStatus::Reviewed, $category->fresh()->schema_status);
        self::assertSame(0, AuditLogEntry::query()->count());
    }

    public static function vetoedLifecycleCases(): array
    {
        $cases = [];
        foreach (['saving', 'updating'] as $event) {
            foreach ([
                [MarkCategorySchemaReviewedAction::class, CategorySchemaStatus::Draft, CannotTransitionCategorySchemaStatusException::class],
                [ApproveCategorySchemaAction::class, CategorySchemaStatus::Reviewed, CannotApproveCategorySchemaException::class],
                [ArchiveCategorySchemaAction::class, CategorySchemaStatus::Approved, CannotTransitionCategorySchemaStatusException::class],
                [RestoreCategorySchemaAction::class, CategorySchemaStatus::Archived, CannotTransitionCategorySchemaStatusException::class],
            ] as [$action, $status, $exception]) {
                $cases[$action.'-'.$event] = [$action, $status, $exception, $event];
            }
        }

        return $cases;
    }

    #[DataProvider('vetoedLifecycleCases')]
    public function test_a_vetoed_lifecycle_save_preserves_state_and_attribution_without_audit(string $action, CategorySchemaStatus $status, string $exceptionClass, string $event): void
    {
        $category = CentralCategory::factory()->create([
            'schema_status' => $status,
            'schema_reviewed_revision' => $status === CategorySchemaStatus::Draft ? null : 1,
            'schema_reviewed_by_user_id' => $status === CategorySchemaStatus::Draft ? null : auth()->id(),
            'schema_reviewed_at' => $status === CategorySchemaStatus::Draft ? null : now(),
            'schema_approved_revision' => in_array($status, [CategorySchemaStatus::Approved, CategorySchemaStatus::Archived], true) ? 1 : null,
            'schema_approved_by_user_id' => in_array($status, [CategorySchemaStatus::Approved, CategorySchemaStatus::Archived], true) ? auth()->id() : null,
            'schema_approved_at' => in_array($status, [CategorySchemaStatus::Approved, CategorySchemaStatus::Archived], true) ? now() : null,
        ]);
        $before = $category->fresh()->getRawOriginal();
        $dispatcher = CentralCategory::getEventDispatcher();
        self::assertNotNull($dispatcher);
        CentralCategory::setEventDispatcher(clone $dispatcher);

        try {
            if ($event === 'saving') {
                CentralCategory::saving(fn (): bool => false);
            } else {
                CentralCategory::updating(fn (): bool => false);
            }
            app($action)->handle($category);
            self::fail('A vetoed model save was accepted.');
        } catch (CannotTransitionCategorySchemaStatusException|CannotApproveCategorySchemaException $exception) {
            self::assertInstanceOf($exceptionClass, $exception);
            self::assertSame('Category schema status could not be persisted.', $exception->getMessage());
        } finally {
            CentralCategory::setEventDispatcher($dispatcher);
        }

        self::assertSame($before, $category->fresh()->getRawOriginal());
        self::assertSame(0, AuditLogEntry::query()->count());
    }
}
