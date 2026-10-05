<?php

namespace Tests\Feature\Categories;

use App\Actions\CategorySchema\ApproveCategorySchemaAction;
use App\Actions\CategorySchema\ArchiveCategorySchemaAction;
use App\Actions\CategorySchema\CloneCategorySchemaAction;
use App\Actions\CategorySchema\CreateAttributeDefinitionAction;
use App\Actions\CategorySchema\CreateAttributeOptionAction;
use App\Actions\CategorySchema\CreateAttributeSectionAction;
use App\Actions\CategorySchema\DeleteAttributeOptionAction;
use App\Actions\CategorySchema\DeleteAttributeSectionAction;
use App\Actions\CategorySchema\MarkCategorySchemaReviewedAction;
use App\Actions\CategorySchema\MoveAttributeDefinitionAction;
use App\Actions\CategorySchema\RestoreCategorySchemaAction;
use App\Actions\CategorySchema\UpdateAttributeDefinitionAction;
use App\Actions\CategorySchema\UpdateAttributeOptionAction;
use App\Actions\CategorySchema\UpdateAttributeSectionAction;
use App\Enums\CategorySchemaStatus;
use App\Exceptions\CategorySchema\CannotApproveCategorySchemaException;
use App\Exceptions\CategorySchema\CannotMoveAttributeDefinitionException;
use App\Exceptions\CategorySchema\CannotTransitionCategorySchemaStatusException;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

final class CategorySchemaRevisionTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actor = User::factory()->centralAdmin()->create();
        $this->actingAs($this->actor);
    }

    private function approve(CentralCategory $category): void
    {
        app(MarkCategorySchemaReviewedAction::class)->handle($category, $category->schema_revision);
        app(ApproveCategorySchemaAction::class)->handle($category, $category->schema_revision);
    }

    public function test_review_and_approval_bind_current_revision_and_actor_then_archive_restore_clear_attribution(): void
    {
        $category = CentralCategory::factory()->create();
        self::assertSame(1, $category->schema_revision);
        $this->approve($category);
        $current = $category->fresh();
        self::assertSame(1, $current->schema_reviewed_revision);
        self::assertSame(1, $current->schema_approved_revision);
        self::assertSame($this->actor->id, $current->schema_reviewed_by_user_id);
        self::assertSame($this->actor->id, $current->schema_approved_by_user_id);
        self::assertNotNull($current->schema_reviewed_at);
        self::assertNotNull($current->schema_approved_at);
        app(ArchiveCategorySchemaAction::class)->handle($category);
        app(RestoreCategorySchemaAction::class)->handle($category);
        $current = $category->fresh();
        self::assertSame(CategorySchemaStatus::Draft, $current->schema_status);
        self::assertSame(1, $current->schema_revision);
        foreach (['schema_reviewed_revision', 'schema_approved_revision', 'schema_reviewed_by_user_id', 'schema_approved_by_user_id', 'schema_reviewed_at', 'schema_approved_at'] as $field) {
            self::assertNull($current->$field);
        }
        self::assertSame(['catalog.category.schema.reviewed', 'catalog.category.schema.approved', 'catalog.category.schema.archived', 'catalog.category.schema.restored'], AuditLogEntry::query()->orderBy('id')->pluck('action')->all());
    }

    public static function mutationCases(): array
    {
        return array_combine(
            ['section-create', 'section-update', 'section-delete', 'attribute-create', 'attribute-update', 'attribute-move', 'option-create', 'option-update', 'option-delete', 'clone'],
            array_map(fn ($v) => [$v], ['section-create', 'section-update', 'section-delete', 'attribute-create', 'attribute-update', 'attribute-move', 'option-create', 'option-update', 'option-delete', 'clone'])
        );
    }

    /** @return callable(): mixed */
    private function mutation(string $case, CentralCategory $category): callable
    {
        if ($case === 'clone') {
            $source = CentralCategory::factory()->create();
            AttributeSection::factory()->for($source, 'category')->create();

            return fn () => app(CloneCategorySchemaAction::class)->handle($source, $category);
        }
        $section = AttributeSection::factory()->for($category, 'category')->create(['name' => 'Specs', 'code' => 'specs']);
        $otherSection = AttributeSection::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()->for($category, 'category')->for($otherSection, 'section')->create(['data_type' => 'enum', 'name' => 'Panel', 'code' => 'panel']);
        $option = AttributeOption::factory()->for($attribute, 'attribute')->create(['code' => 'ips', 'label' => 'IPS']);

        return match ($case) {
            'section-create' => fn () => app(CreateAttributeSectionAction::class)->handle($category, ['name' => 'New', 'code' => 'new']),
            'section-update' => fn () => app(UpdateAttributeSectionAction::class)->handle($section, ['name' => 'Changed', 'code' => 'specs']),
            'section-delete' => fn () => app(DeleteAttributeSectionAction::class)->handle($section),
            'attribute-create' => fn () => app(CreateAttributeDefinitionAction::class)->handle($section, ['name' => 'New', 'code' => 'new', 'data_type' => 'string']),
            'attribute-update' => fn () => app(UpdateAttributeDefinitionAction::class)->handle($attribute, ['name' => 'Changed', 'code' => 'panel', 'data_type' => 'enum']),
            'attribute-move' => fn () => app(MoveAttributeDefinitionAction::class)->handle($attribute, $section, 1),
            'option-create' => fn () => app(CreateAttributeOptionAction::class)->handle($attribute, ['code' => 'tn', 'label' => 'TN']),
            'option-update' => fn () => app(UpdateAttributeOptionAction::class)->handle($option, ['code' => 'ips', 'label' => 'Changed']),
            'option-delete' => fn () => app(DeleteAttributeOptionAction::class)->handle($option),
            default => throw new \InvalidArgumentException('Unknown test mutation'),
        };
    }

    #[DataProvider('mutationCases')]
    public function test_every_existing_schema_mutation_invalidates_once_and_clears_attribution(string $case): void
    {
        $category = CentralCategory::factory()->create();
        $mutation = $this->mutation($case, $category);
        $this->approve($category);
        if ($case === 'option-delete') {
            $before = $category->fresh()->getRawOriginal();
            $auditCount = AuditLogEntry::query()->count();
            try {
                $mutation();
                self::fail('Hard option removal was allowed.');
            } catch (ValidationException) {
                self::assertSame($before, $category->fresh()->getRawOriginal());
                self::assertSame($auditCount, AuditLogEntry::query()->count());
            }

            return;
        }
        $mutation();
        $current = $category->fresh();
        self::assertSame(2, $current->schema_revision);
        self::assertSame(CategorySchemaStatus::Draft, $current->schema_status);
        foreach (['schema_reviewed_revision', 'schema_approved_revision', 'schema_reviewed_by_user_id', 'schema_approved_by_user_id', 'schema_reviewed_at', 'schema_approved_at'] as $field) {
            self::assertNull($current->$field);
        }
        $event = AuditLogEntry::query()->where('action', 'catalog.category.schema.invalidated')->sole();
        self::assertSame(1, $event->before_json['schema_revision']);
        self::assertSame(2, $event->after_json['schema_revision']);
        self::assertSame($category->id, $event->after_json['category_id']);
        self::assertSame($this->actor->id, $event->actor_id);
        self::assertNull($event->site_id);
        self::assertLessThanOrEqual(11, count($event->after_json));
        self::assertArrayNotHasKey('config_json', $event->after_json);
        self::assertArrayNotHasKey('schema_reviewed_at', $event->after_json);
    }

    #[DataProvider('mutationCases')]
    public function test_archived_schema_rejects_all_existing_mutation_paths_without_partial_writes(string $case): void
    {
        $category = CentralCategory::factory()->create();
        $mutation = $this->mutation($case, $category);
        $category->update(['schema_status' => CategorySchemaStatus::Archived]);
        try {
            $mutation();
            self::fail('Archived schema accepted mutation');
        } catch (ValidationException) {
            self::assertSame(1, $category->fresh()->schema_revision);
            self::assertSame(CategorySchemaStatus::Archived, $category->fresh()->schema_status);
            self::assertSame(0, AuditLogEntry::query()->count());
        }
    }

    public function test_no_op_section_definition_option_move_and_empty_clone_do_not_invalidate(): void
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create(['code' => 'specs', 'name' => 'Specs']);
        $attribute = AttributeDefinition::factory()->for($category, 'category')->for($section, 'section')->create(['code' => 'panel', 'name' => 'Panel', 'data_type' => 'enum']);
        $option = AttributeOption::factory()->for($attribute, 'attribute')->create(['code' => 'ips', 'label' => 'IPS']);
        $this->approve($category);
        app(UpdateAttributeSectionAction::class)->handle($section, ['code' => 'specs', 'name' => 'Specs']);
        app(UpdateAttributeDefinitionAction::class)->handle($attribute, ['code' => 'panel', 'name' => 'Panel', 'data_type' => 'enum']);
        app(UpdateAttributeOptionAction::class)->handle($option, ['code' => 'ips', 'label' => 'IPS']);
        app(MoveAttributeDefinitionAction::class)->handle($attribute, $section, $attribute->position);
        self::assertSame(1, $category->fresh()->schema_revision);
        self::assertSame(CategorySchemaStatus::Approved, $category->fresh()->schema_status);
        self::assertSame(0, AuditLogEntry::query()->where('action', 'catalog.category.schema.invalidated')->count());
        $source = CentralCategory::factory()->create();
        $target = CentralCategory::factory()->create();
        app(CloneCategorySchemaAction::class)->handle($source, $target);
        self::assertSame(1, $target->fresh()->schema_revision);
        self::assertSame(2, AuditLogEntry::query()->count());
    }

    public function test_reviewed_mutation_returns_to_draft_and_stale_review_and_approval_are_rejected(): void
    {
        $category = CentralCategory::factory()->create();
        app(MarkCategorySchemaReviewedAction::class)->handle($category, 1);
        app(CreateAttributeSectionAction::class)->handle($category, ['name' => 'Specs', 'code' => 'specs']);
        self::assertSame(CategorySchemaStatus::Draft, $category->fresh()->schema_status);
        foreach ([MarkCategorySchemaReviewedAction::class, ApproveCategorySchemaAction::class] as $action) {
            try {
                app($action)->handle($category, 1);
                self::fail('Stale lifecycle command succeeded');
            } catch (ValidationException) {
                self::assertSame(2, AuditLogEntry::query()->count());
            }
        }
    }

    public function test_review_validates_fresh_schema_and_approval_requires_same_reviewed_revision(): void
    {
        $category = CentralCategory::factory()->create();
        $category->forceFill(['schema_status' => CategorySchemaStatus::Reviewed, 'schema_reviewed_revision' => 0])->save();
        try {
            app(ApproveCategorySchemaAction::class)->handle($category, 1);
            self::fail('Mismatched review approved');
        } catch (CannotApproveCategorySchemaException) {
            self::assertSame(0, AuditLogEntry::query()->count());
        }
        $category->update(['schema_status' => CategorySchemaStatus::Draft]);
        $attribute = AttributeDefinition::factory()->for($category, 'category')->create(['data_type' => 'decimal']);
        AttributeOption::factory()->for($attribute, 'attribute')->create();
        $this->expectException(CannotTransitionCategorySchemaStatusException::class);
        $this->expectExceptionMessage('Category schema cannot be marked reviewed while validation errors exist.');
        app(MarkCategorySchemaReviewedAction::class)->handle($category, 1);
    }

    public function test_audit_failure_rolls_back_schema_content_revision_and_approval(): void
    {
        $category = CentralCategory::factory()->create();
        $this->approve($category);
        $audit = Mockery::mock(AuditRecorder::class);
        $audit->shouldReceive('record')->once()->andThrow(new RuntimeException('audit unavailable'));
        $this->app->instance(AuditRecorder::class, $audit);
        try {
            app(CreateAttributeSectionAction::class)->handle($category, ['name' => 'Specs', 'code' => 'specs']);
            self::fail('Audit failure expected');
        } catch (RuntimeException) {
            self::assertSame(0, $category->attributeSections()->count());
            self::assertSame(1, $category->fresh()->schema_revision);
            self::assertSame(CategorySchemaStatus::Approved, $category->fresh()->schema_status);
            self::assertSame(1, $category->fresh()->schema_approved_revision);
        }
    }

    public static function positionCases(): array
    {
        return array_map(fn ($case) => [$case], [
            'section-create', 'section-update', 'attribute-create', 'attribute-update',
            'option-create', 'option-update', 'attribute-move',
        ]);
    }

    #[DataProvider('positionCases')]
    public function test_positions_outside_the_portable_integer_range_are_rejected_before_writes(string $case): void
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()->for($category, 'category')->for($section, 'section')->create(['data_type' => 'enum']);
        $option = AttributeOption::factory()->for($attribute, 'attribute')->create();
        $this->approve($category);
        $position = 2147483648;
        try {
            match ($case) {
                'section-create' => app(CreateAttributeSectionAction::class)->handle($category, ['name' => 'New', 'code' => 'new', 'position' => $position]),
                'section-update' => app(UpdateAttributeSectionAction::class)->handle($section, ['name' => $section->name, 'code' => $section->code, 'position' => $position]),
                'attribute-create' => app(CreateAttributeDefinitionAction::class)->handle($section, ['name' => 'New', 'code' => 'new', 'data_type' => 'string', 'position' => $position]),
                'attribute-update' => app(UpdateAttributeDefinitionAction::class)->handle($attribute, ['name' => $attribute->name, 'code' => $attribute->code, 'data_type' => 'enum', 'position' => $position]),
                'option-create' => app(CreateAttributeOptionAction::class)->handle($attribute, ['code' => 'new', 'label' => 'New', 'position' => $position]),
                'option-update' => app(UpdateAttributeOptionAction::class)->handle($option, ['code' => $option->code, 'label' => $option->label, 'position' => $position]),
                'attribute-move' => app(MoveAttributeDefinitionAction::class)->handle($attribute, $section, $position),
                default => throw new \InvalidArgumentException('Unknown position case'),
            };
            self::fail('Nonportable position accepted');
        } catch (ValidationException|CannotMoveAttributeDefinitionException) {
            self::assertSame(1, $category->fresh()->schema_revision);
            self::assertSame(CategorySchemaStatus::Approved, $category->fresh()->schema_status);
            self::assertSame($section->position, $section->fresh()->position);
            self::assertSame($attribute->position, $attribute->fresh()->position);
            self::assertSame($option->position, $option->fresh()->position);
            self::assertSame(0, AuditLogEntry::query()->where('action', 'catalog.category.schema.invalidated')->count());
        }
    }

    public function test_move_rejects_target_shift_overflow_without_invalidating_approval(): void
    {
        $category = CentralCategory::factory()->create();
        $source = AttributeSection::factory()->for($category, 'category')->create();
        $target = AttributeSection::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()->for($category, 'category')->for($source, 'section')->create();
        $last = AttributeDefinition::factory()->for($category, 'category')->for($target, 'section')->create(['position' => 2147483647]);
        $this->approve($category);
        try {
            app(MoveAttributeDefinitionAction::class)->handle($attribute, $target, 0);
            self::fail('Overflow shift accepted');
        } catch (CannotMoveAttributeDefinitionException) {
            self::assertSame($source->id, $attribute->fresh()->attribute_section_id);
            self::assertSame(2147483647, $last->fresh()->position);
            self::assertSame(1, $category->fresh()->schema_revision);
            self::assertSame(CategorySchemaStatus::Approved, $category->fresh()->schema_status);
            self::assertSame(0, AuditLogEntry::query()->where('action', 'catalog.category.schema.invalidated')->count());
        }
    }

    public function test_sectionless_move_does_not_reorder_another_category_or_leave_its_schema_silently_changed(): void
    {
        $category = CentralCategory::factory()->create();
        $other = CentralCategory::factory()->create();
        $target = AttributeSection::factory()->for($category, 'category')->create();
        $moving = AttributeDefinition::factory()->for($category, 'category')->create(['position' => 0]);
        $remaining = AttributeDefinition::factory()->for($category, 'category')->create(['position' => 1]);
        $foreign = AttributeDefinition::factory()->for($other, 'category')->create(['position' => 1]);
        $this->approve($category);
        $this->approve($other);
        app(MoveAttributeDefinitionAction::class)->handle($moving, $target, 0);
        self::assertSame(0, $remaining->fresh()->position);
        self::assertSame(1, $foreign->fresh()->position);
        self::assertSame(1, $other->fresh()->schema_revision);
        self::assertSame(CategorySchemaStatus::Approved, $other->fresh()->schema_status);
        self::assertSame(2, $category->fresh()->schema_revision);
        self::assertSame([$category->id], AuditLogEntry::query()->where('action', 'catalog.category.schema.invalidated')->get()->map(fn ($event) => $event->after_json['category_id'])->all());
    }

    public function test_option_update_uses_current_ownership_instead_of_a_cached_attribute_relation(): void
    {
        $old = AttributeDefinition::factory()->create(['data_type' => 'enum']);
        $current = AttributeDefinition::factory()->create(['data_type' => 'enum']);
        $option = AttributeOption::factory()->for($old, 'attribute')->create()->load('attribute');
        // Simulate a stale caller snapshot of legacy data, not an ownership action.
        AttributeOption::query()->whereKey($option->id)->update(['attribute_definition_id' => $current->id]);
        $this->approve($old->category);
        $this->approve($current->category);
        app(UpdateAttributeOptionAction::class)->handle($option, ['code' => $option->code, 'label' => 'Changed']);
        self::assertSame('Changed', $option->fresh()->label);
        self::assertSame(1, $old->category->fresh()->schema_revision);
        self::assertSame(CategorySchemaStatus::Approved, $old->category->fresh()->schema_status);
        self::assertSame(2, $current->category->fresh()->schema_revision);
        self::assertSame(CategorySchemaStatus::Draft, $current->category->fresh()->schema_status);
        self::assertSame($current->central_category_id, AuditLogEntry::query()->where('action', 'catalog.category.schema.invalidated')->sole()->after_json['category_id']);
    }
}
