<?php

namespace Tests\Feature\SchemaConsumers;

use App\Actions\CategorySchema\SaveCategoryComparisonAction;
use App\Actions\CategorySchema\UpdateCategoryAttributeAssignmentAction;
use App\Actions\Facets\SaveCategoryFacetAction;
use App\Actions\Imports\PublishNormalizedProductDraftToCentralAction;
use App\Actions\Imports\SaveAttributeMappingAction;
use App\Actions\ProductAttributes\SaveProductSpecsAction;
use App\Enums\CategorySchemaStatus;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\AttributeIdentityScope;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CategoryComparisonAttribute;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\FacetDefinition;
use App\Models\Imports\AttributeMapping;
use App\Models\Imports\ImportSource;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

final class SchemaConsumerMutationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('schemaOwners')]
    public function test_authoritative_configuration_invalidates_once_noop_preserves_epoch_and_archived_rejects(string $owner): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        $category = $assignment->category;
        $category->forceFill(['schema_status' => CategorySchemaStatus::Approved, 'schema_reviewed_revision' => 1,
            'schema_approved_revision' => 1, 'schema_reviewed_by_user_id' => $actor->id, 'schema_reviewed_at' => now(),
            'schema_approved_by_user_id' => $actor->id, 'schema_approved_at' => now()])->save();
        $save = function (int $revision, bool $visible) use ($owner, $assignment, $actor, $category): void {
            if ($owner === 'facet') {
                app(SaveCategoryFacetAction::class)->handle($category, FacetDefinition::query()->first(),
                    ['code' => 'maker', 'facet_type' => 'checkbox', 'source_type' => 'brand', 'is_visible' => $visible], $revision, $actor);
            } else {
                app(SaveCategoryComparisonAction::class)->handle($category,
                    [['category_attribute_assignment_id' => $assignment->id, 'position' => 0, 'is_visible' => $visible]], $revision, $actor);
            }
        };
        $save(1, true);
        self::assertSame(2, $category->fresh()->schema_revision);
        self::assertSame(CategorySchemaStatus::Draft, $category->fresh()->schema_status);
        self::assertNull($category->fresh()->schema_approved_at);
        $epoch = AttributeIdentityScope::query()->value('write_epoch');
        $audit = AuditLogEntry::query()->count();
        $save(2, true);
        self::assertSame($epoch, AttributeIdentityScope::query()->value('write_epoch'));
        self::assertSame($audit, AuditLogEntry::query()->count());
        $category->forceFill(['schema_status' => CategorySchemaStatus::Archived])->save();
        try {
            $save(2, false);
            self::fail('Archived configuration mutated');
        } catch (ValidationException) {
            self::assertSame(2, $category->fresh()->schema_revision);
            self::assertSame($audit, AuditLogEntry::query()->count());
        }
    }

    #[DataProvider('schemaOwners')]
    public function test_audit_failure_rolls_back_configuration_revision_and_epoch(string $owner): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        $epoch = AttributeIdentityScope::query()->value('write_epoch');
        $this->mock(AuditRecorder::class)->shouldReceive('record')->andThrow(new RuntimeException('audit unavailable'));
        try {
            if ($owner === 'facet') {
                app(SaveCategoryFacetAction::class)->handle($assignment->category, null, ['code' => 'maker', 'facet_type' => 'checkbox', 'source_type' => 'brand'], 1, $actor);
            } else {
                app(SaveCategoryComparisonAction::class)->handle($assignment->category, [['category_attribute_assignment_id' => $assignment->id, 'position' => 0, 'is_visible' => true]], 1, $actor);
            }
            self::fail('Audit failure committed');
        } catch (RuntimeException) {
            self::assertSame(0, FacetDefinition::query()->count());
            self::assertSame(0, CategoryComparisonAttribute::query()->count());
            self::assertSame(1, $assignment->category->fresh()->schema_revision);
            self::assertSame($epoch, AttributeIdentityScope::query()->value('write_epoch'));
        }
    }

    public static function schemaOwners(): array
    {
        return [['facet'], ['comparison']];
    }

    public function test_foreign_assignment_facet_comparison_and_mapping_fail_transactionally(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        $foreign = CategoryAttributeAssignment::factory()->create();
        $source = ImportSource::factory()->create();
        foreach ([
            fn () => app(SaveCategoryFacetAction::class)->handle($assignment->category, null, ['code' => 'foreign', 'source_type' => 'attribute', 'facet_type' => 'range', 'category_attribute_assignment_id' => $foreign->id], 1, $actor),
            fn () => app(SaveCategoryComparisonAction::class)->handle($assignment->category, [['category_attribute_assignment_id' => $foreign->id, 'position' => 0, 'is_visible' => true]], 1, $actor),
            fn () => app(SaveAttributeMappingAction::class)->handle(null, ['category_id' => $assignment->central_category_id, 'import_source_id' => $source->id, 'raw_key' => 'Spec', 'status' => 'reviewed', 'confidence' => 1, 'mapping_type' => 'attribute', 'category_attribute_assignment_id' => $foreign->id], $actor),
        ] as $write) {
            try {
                $write();
                self::fail('Foreign assignment accepted');
            } catch (ValidationException) {
                self::assertSame(0, FacetDefinition::query()->count());
                self::assertSame(0, CategoryComparisonAttribute::query()->count());
                self::assertSame(0, AttributeMapping::query()->count());
                self::assertSame(0, AuditLogEntry::query()->count());
                self::assertSame(1, $assignment->category->fresh()->schema_revision);
            }
        }
    }

    public function test_publish_category_change_revalidates_existing_facts_and_rolls_back_product(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        $fact = CentralProductAttributeValue::factory()->forAssignment($assignment)->create();
        $foreign = CategoryAttributeAssignment::factory()->create();
        $before = $fact->fresh()->getRawOriginal();
        $draft = NormalizedProductDraft::factory()->create(['category_id' => $foreign->central_category_id,
            'matched_central_product_id' => $fact->central_product_id, 'status' => 'approved', 'title' => 'Forbidden move']);
        try {
            app(PublishNormalizedProductDraftToCentralAction::class)->handle($draft, $actor);
            self::fail('Category move orphaned a fact');
        } catch (\LogicException) {
            self::assertSame($assignment->central_category_id, $fact->product->fresh()->central_category_id);
            self::assertSame($before, $fact->fresh()->getRawOriginal());
            self::assertSame('approved', $draft->fresh()->status);
        }
    }

    public function test_guest_disabled_and_category_only_actors_cannot_mutate_new_schema_owners(): void
    {
        $assignment = CategoryAttributeAssignment::factory()->create();
        $categoryOnly = User::factory()->create(['role' => 'catalog_editor']);
        config(['cataloghub_permissions.roles.catalog_editor' => ['central.panel.access', 'central.page.access', 'central.mutation.execute', 'catalog.categories.manage']]);
        foreach ([null, User::factory()->centralAdmin()->disabled()->create(), $categoryOnly, User::factory()->create(['role' => 'translator']), User::factory()->create(['role' => 'moderator'])] as $actor) {
            try {
                app(SaveCategoryComparisonAction::class)->handle($assignment->category, [], 1, $actor);
                self::fail('Unauthorized actor admitted');
            } catch (AuthorizationException) {
                self::assertSame(0, AuditLogEntry::query()->count());
            }
        }
    }

    public function test_stale_specs_form_cannot_commit_after_assignment_change(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        $product = CentralProduct::factory()->for($assignment->category, 'category')->create();
        app(UpdateCategoryAttributeAssignmentAction::class)->handle($assignment, ['is_visible' => false], 1, $actor);
        try {
            app(SaveProductSpecsAction::class)->handle($product, [$assignment->attribute_definition_id => ['value_text' => 'Stale']], $actor, 1);
            self::fail('Stale specs committed');
        } catch (ValidationException $error) {
            self::assertArrayHasKey('schema_revision', $error->errors());
            self::assertSame(0, CentralProductAttributeValue::query()->count());
        }
    }
}
