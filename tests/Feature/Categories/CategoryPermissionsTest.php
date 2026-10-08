<?php

namespace Tests\Feature\Categories;

use App\Actions\CategorySchema\CreateAttributeSectionAction;
use App\Actions\CategorySchema\MarkCategorySchemaReviewedAction;
use App\Actions\CategorySchema\RestoreCategorySchemaAction;
use App\Actions\CentralCatalog\CreateCentralCategoryAction;
use App\Enums\CentralCategoryStatus;
use App\Enums\UserRole;
use App\Filament\Resources\CentralCategoryResource;
use App\Filament\Resources\CentralCategoryResource\Pages\CategorySchemaBuilder;
use App\Filament\Resources\CentralCategoryResource\Pages\CreateCentralCategory;
use App\Filament\Resources\CentralCategoryResource\Pages\EditCentralCategory;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class CategoryPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_only_can_use_category_resource_but_not_schema_routes_or_actions(): void
    {
        $actor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $category = CentralCategory::factory()->create();
        $this->actingAs($actor)->get(CentralCategoryResource::getUrl('index'))->assertOk();
        $this->get(CategorySchemaBuilder::getUrl(['record' => $category]))->assertForbidden();
        try {
            app(CreateAttributeSectionAction::class)->handle($category, ['name' => 'Specs', 'code' => 'specs']);
            self::fail('Category permission mutated schema');
        } catch (AuthorizationException) {
            self::assertSame(0, $category->attributeSections()->count());
            self::assertSame(0, AuditLogEntry::query()->count());
        }
        app(CreateCentralCategoryAction::class)->handle($actor, ['name' => 'Allowed', 'slug' => 'allowed'], 0);
    }

    public function test_schema_only_actor_reaches_schema_without_category_capability_and_cannot_create_categories(): void
    {
        config(['cataloghub_permissions.roles.catalog_editor' => ['central.panel.access', 'central.page.access', 'central.mutation.execute', 'catalog.schema.manage']]);
        $actor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $category = CentralCategory::factory()->create();
        $this->actingAs($actor)->get(CategorySchemaBuilder::getUrl(['record' => $category]))->assertOk();
        $this->get(CentralCategoryResource::getUrl('index'))->assertForbidden();
        app(CreateAttributeSectionAction::class)->handle($category, ['name' => 'Specs', 'code' => 'specs']);
        self::assertSame(2, $category->fresh()->schema_revision);
        $this->expectException(AuthorizationException::class);
        app(CreateCentralCategoryAction::class)->handle($actor, ['name' => 'Denied', 'slug' => 'denied'], 0);
    }

    public function test_translation_site_only_disabled_and_guest_actors_fail_closed(): void
    {
        $category = CentralCategory::factory()->create();
        foreach ([UserRole::Translator, UserRole::SiteAdmin, UserRole::Moderator] as $role) {
            $actor = User::factory()->create(['role' => $role]);
            $this->actingAs($actor)->get(CentralCategoryResource::getUrl('index'))->assertForbidden();
            $this->get(CategorySchemaBuilder::getUrl(['record' => $category]))->assertForbidden();
            foreach ([false, true] as $schema) {
                try {
                    if ($schema) {
                        app(MarkCategorySchemaReviewedAction::class)->handle($category, 1, $actor);
                    } else {
                        app(CreateCentralCategoryAction::class)->handle($actor, ['name' => 'Denied', 'slug' => 'denied'], 0);
                    }
                    self::fail('Unauthorized mutation succeeded');
                } catch (AuthorizationException) {
                    self::assertSame(0, AuditLogEntry::query()->count());
                }
            }
        }
        $disabled = User::factory()->centralAdmin()->disabled()->create();
        try {
            app(CreateCentralCategoryAction::class)->handle($disabled, ['name' => 'Denied', 'slug' => 'denied'], 0);
            self::fail('Disabled mutation succeeded');
        } catch (AuthorizationException) {
            self::assertSame(1, CentralCategory::query()->count());
        }
        auth()->logout();
        $this->expectException(AuthorizationException::class);
        app(CreateAttributeSectionAction::class)->handle($category, ['name' => 'Denied', 'code' => 'denied']);
    }

    public function test_read_capability_does_not_imply_central_mutation_and_gets_are_read_only(): void
    {
        config(['cataloghub_permissions.roles.catalog_editor' => ['central.panel.access', 'central.page.access', 'catalog.schema.manage', 'catalog.categories.manage']]);
        $actor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $category = CentralCategory::factory()->create();
        $this->actingAs($actor);
        $before = $category->fresh()->toJson();
        $this->get(CentralCategoryResource::getUrl('index'))->assertOk();
        $this->get(CategorySchemaBuilder::getUrl(['record' => $category]))->assertOk();
        self::assertFalse(CentralCategoryResource::canCreate());
        self::assertFalse(CentralCategoryResource::canEdit($category));
        foreach ([false, true] as $schema) {
            try {
                if ($schema) {
                    app(CreateAttributeSectionAction::class)->handle($category, ['name' => 'Denied', 'code' => 'denied']);
                } else {
                    app(CreateCentralCategoryAction::class)->handle($actor, ['name' => 'Denied', 'slug' => 'denied'], 0);
                }
                self::fail('Missing Central mutation accepted');
            } catch (AuthorizationException) {
                self::assertSame(0, AuditLogEntry::query()->count());
            }
        }
        self::assertSame($before, $category->fresh()->toJson());
    }

    public function test_legacy_create_edit_parent_and_lifecycle_use_authoritative_actions(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $this->actingAs($actor);
        Livewire::test(CreateCentralCategory::class)->fillForm(['name' => 'Parent', 'slug' => 'parent'])->call('create')->assertHasNoFormErrors();
        $parent = CentralCategory::query()->sole();
        Livewire::test(CreateCentralCategory::class)->fillForm(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id, 'new_hierarchy_revision' => 0])->call('create')->assertHasNoFormErrors();
        $child = CentralCategory::query()->where('slug', 'child')->sole();
        self::assertSame($parent->id, $child->parent_id);
        Livewire::test(EditCentralCategory::class, ['record' => $child->id])
            ->fillForm(['name' => 'Changed', 'parent_id' => null, 'new_hierarchy_revision' => 1])->call('save')->assertHasNoFormErrors();
        self::assertNull($child->fresh()->parent_id);
        self::assertSame('Changed', $child->fresh()->name);
        self::assertSame(1, $child->fresh()->position);
        self::assertSame(1, AuditLogEntry::query()->where('action', 'catalog.category.reparented')->count());
        self::assertSame(1, AuditLogEntry::query()->where('action', 'catalog.category.updated')->count());
        Livewire::test(EditCentralCategory::class, ['record' => $child->id])->callAction('archive')->assertHasNoActionErrors();
        self::assertSame('archived', $child->fresh()->status->value);
    }

    public function test_legacy_native_reorder_and_status_payloads_cannot_bypass_actions(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $this->actingAs($actor);
        $a = CentralCategory::factory()->create(['position' => 0]);
        $b = CentralCategory::factory()->create(['position' => 1]);
        $this->get('/admin/central/central-categories')->assertRedirect(route('central.categories.index'));
        self::assertSame([0, 1], [$a->fresh()->position, $b->fresh()->position]);
        Livewire::test(EditCentralCategory::class, ['record' => $a->id])
            ->fillForm(['status' => 'active', 'schema_status' => 'approved'])
            ->call('save')->assertHasNoFormErrors();
        self::assertSame('draft', $a->fresh()->status->value);
        self::assertSame('draft', $a->fresh()->schema_status->value);
        self::assertSame(0, AuditLogEntry::query()->count());
    }

    public function test_lifecycle_actions_refresh_the_current_page_record_form_and_header_visibility(): void
    {
        $this->actingAs(User::factory()->centralAdmin()->create());
        $category = CentralCategory::factory()->create();
        $page = Livewire::test(EditCentralCategory::class, ['record' => $category->id]);
        $page->assertSet('data.status', 'draft')->assertActionVisible('activate')
            ->assertActionVisible('archive')->assertActionHidden('restore');
        foreach ([['activate', CentralCategoryStatus::Active], ['archive', CentralCategoryStatus::Archived], ['restore', CentralCategoryStatus::Draft]] as [$action, $status]) {
            $page->callAction($action)->assertHasNoActionErrors()->assertSet('data.status', $status->value);
            $component = $page->instance();
            self::assertInstanceOf(EditCentralCategory::class, $component);
            $record = $component->getRecord();
            self::assertInstanceOf(CentralCategory::class, $record);
            self::assertSame($status, $record->status);
            self::assertSame($status, $category->fresh()->status);
            if ($status === CentralCategoryStatus::Draft) {
                $page->assertActionVisible('activate')->assertActionVisible('archive')->assertActionHidden('restore');
            } elseif ($status === CentralCategoryStatus::Active) {
                $page->assertActionHidden('activate')->assertActionVisible('archive')->assertActionHidden('restore');
            } else {
                $page->assertActionHidden('activate')->assertActionHidden('archive')->assertActionVisible('restore');
            }
        }
    }

    public function test_concurrent_schema_restore_rejection_is_a_validation_response(): void
    {
        $this->actingAs(User::factory()->centralAdmin()->create());
        $category = CentralCategory::factory()->create(['schema_status' => 'archived']);
        $page = Livewire::test(CategorySchemaBuilder::class, ['record' => $category->id]);
        app(RestoreCategorySchemaAction::class)->handle($category);
        app(MarkCategorySchemaReviewedAction::class)->handle($category->fresh());
        $before = $category->fresh()->getRawOriginal();
        $auditCount = AuditLogEntry::query()->count();
        $page->call('restoreSchema')->assertHasErrors(['schema_status']);
        self::assertSame($before, $category->fresh()->getRawOriginal());
        self::assertSame($auditCount, AuditLogEntry::query()->count());
    }

    public function test_clearing_the_parent_select_with_an_empty_string_reparents_to_root(): void
    {
        $this->actingAs(User::factory()->centralAdmin()->create());
        $parent = CentralCategory::factory()->create(['position' => 0]);
        $child = CentralCategory::factory()->create(['parent_id' => $parent->id, 'position' => 0]);
        Livewire::test(EditCentralCategory::class, ['record' => $child->id])
            ->fillForm(['parent_id' => '', 'new_hierarchy_revision' => 0])
            ->call('save')->assertHasNoFormErrors()->assertSet('data.parent_id', null);
        self::assertNull($child->fresh()->parent_id);
        self::assertSame(1, $child->fresh()->position);
        self::assertSame(1, AuditLogEntry::query()->where('action', 'catalog.category.reparented')->count());
    }

    public function test_legacy_identity_validation_failure_rolls_back_combined_reparent_and_audit(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $this->actingAs($actor);
        $parent = CentralCategory::factory()->create();
        $child = CentralCategory::factory()->create(['parent_id' => $parent->id]);
        Livewire::test(EditCentralCategory::class, ['record' => $child->id])
            ->fillForm(['slug' => $parent->slug, 'parent_id' => null, 'new_hierarchy_revision' => 0])->call('save')->assertHasFormErrors(['slug']);
        self::assertSame($parent->id, $child->fresh()->parent_id);
        self::assertSame(0, AuditLogEntry::query()->count());
    }
}
