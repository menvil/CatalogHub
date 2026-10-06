<?php

namespace Tests\Feature\Categories;

use App\Actions\CentralCatalog\ActivateCentralCategoryAction;
use App\Actions\CentralCatalog\ArchiveCentralCategoryAction;
use App\Actions\CentralCatalog\RestoreCentralCategoryAction;
use App\Enums\CategorySchemaStatus;
use App\Enums\CentralCategoryStatus;
use App\Filament\Resources\CentralCategoryResource;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\Translations\AttributeSectionTranslation;
use App\Models\Translations\CategoryTranslation;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class CategoryLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_lifecycle_transitions_are_independent_of_schema_and_publication(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $category = CentralCategory::factory()->create(['schema_status' => CategorySchemaStatus::Draft]);
        app(ActivateCentralCategoryAction::class)->handle($actor, $category);
        self::assertSame(CentralCategoryStatus::Active, $category->fresh()->status);
        self::assertSame(CategorySchemaStatus::Draft, $category->fresh()->schema_status);
        self::assertSame(0, SiteCategory::query()->count());
        app(ActivateCentralCategoryAction::class)->handle($actor, $category);
        app(ArchiveCentralCategoryAction::class)->handle($actor, $category);
        app(ArchiveCentralCategoryAction::class)->handle($actor, $category);
        app(RestoreCentralCategoryAction::class)->handle($actor, $category);
        self::assertSame(CentralCategoryStatus::Draft, $category->fresh()->status);
        self::assertSame(['catalog.category.activated', 'catalog.category.archived', 'catalog.category.restored'], AuditLogEntry::query()->orderBy('id')->pluck('action')->all());
        foreach (AuditLogEntry::query()->get() as $event) {
            self::assertSame(['status'], array_keys($event->after_json));
            self::assertNull($event->site_id);
            self::assertSame($actor->id, $event->actor_id);
        }
    }

    public function test_archive_from_draft_preserves_every_canonical_dependency_and_hard_delete_is_disabled(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $category = CentralCategory::factory()->create(['schema_status' => CategorySchemaStatus::Approved, 'schema_reviewed_revision' => 1, 'schema_approved_revision' => 1]);
        $child = CentralCategory::factory()->create(['parent_id' => $category->id]);
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $ungroupedSection = AttributeSection::factory()->for($category, 'category')->create();
        $definition = AttributeDefinition::factory()->assignedTo($category)->state(['attribute_section_id' => $section->id])->create(['data_type' => 'enum']);
        $option = AttributeOption::factory()->for($definition, 'attribute')->create();
        $product = CentralProduct::factory()->create(['central_category_id' => $category->id]);
        $value = CentralProductAttributeValue::factory()->create(['central_product_id' => $product->id, 'attribute_definition_id' => $definition->id]);
        $selection = SiteCategory::query()->create(['site_id' => Site::factory()->create()->id, 'central_category_id' => $category->id]);
        $translation = CategoryTranslation::factory()->create(['category_id' => $category->id]);
        $sectionTranslation = AttributeSectionTranslation::factory()->create(['attribute_section_id' => $section->id]);
        app(ArchiveCentralCategoryAction::class)->handle($actor, $category);
        self::assertSame(CentralCategoryStatus::Archived, $category->fresh()->status);
        self::assertSame($category->id, $product->fresh()->central_category_id);
        self::assertSame($category->id, $selection->fresh()->central_category_id);
        self::assertSame($category->id, $child->fresh()->parent_id);
        foreach ([$section, $ungroupedSection, $definition, $option, $value, $translation, $sectionTranslation] as $row) {
            self::assertNotNull($row->fresh());
        }
        self::assertSame(CategorySchemaStatus::Approved, $category->fresh()->schema_status);
        self::assertSame(1, $category->fresh()->schema_approved_revision);
        $this->actingAs($actor);
        self::assertFalse(CentralCategoryResource::canDelete($category));
        self::assertFalse(CentralCategoryResource::canDeleteAny());
    }

    public function test_cannot_activate_archived_or_restore_active_and_rejections_do_not_audit(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $archived = CentralCategory::factory()->create(['status' => 'archived']);
        $active = CentralCategory::factory()->create(['status' => 'active']);
        foreach ([[ActivateCentralCategoryAction::class, $archived], [RestoreCentralCategoryAction::class, $active]] as [$action, $category]) {
            try {
                app($action)->handle($actor, $category);
                self::fail('Illegal transition succeeded');
            } catch (ValidationException) {
                self::assertSame(0, AuditLogEntry::query()->count());
            }
        }
    }

    public function test_audit_failure_rolls_back_lifecycle(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $category = CentralCategory::factory()->create();
        $audit = Mockery::mock(AuditRecorder::class);
        $audit->shouldReceive('record')->once()->andThrow(new RuntimeException('audit unavailable'));
        $this->app->instance(AuditRecorder::class, $audit);
        try {
            app(ArchiveCentralCategoryAction::class)->handle($actor, $category);
            self::fail('Audit failure expected');
        } catch (RuntimeException) {
            self::assertSame(CentralCategoryStatus::Draft, $category->fresh()->status);
        }
    }
}
