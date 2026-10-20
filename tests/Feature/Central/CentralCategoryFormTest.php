<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\SiteCategory;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Categories\CategoryHierarchy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Support\BuildsCategorySaveData;
use Tests\Support\CategoryCreateEditFixture as Fixture;
use Tests\TestCase;

final class CentralCategoryFormTest extends TestCase
{
    use BuildsCategorySaveData, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Fixture::create();
        $this->admin = User::factory()->centralAdmin()->create();
    }

    public function test_create_form_renders_an_empty_focused_canonical_form_without_fabricated_context(): void
    {
        $page = $this->actingAs($this->admin)->get(route('central.categories.create'))->assertOk()
            ->assertSee('data-screen-id="CA-018"', false)->assertSee('data-category-form-mode="create"', false)
            ->assertSee('Create Category')->assertSee('Parent Category')->assertSee('Category Name')->assertSee('Slug')
            ->assertSee('Root Category (no parent)')->assertSee('Electronics / Displays / Monitors')
            ->assertSee('Legacy Peripherals · Archived')->assertSee('Save Category')
            ->assertSee('Starts as <strong>Draft</strong>', false)
            ->assertSee('href="'.route('central.categories.index').'"', false);
        $form = $this->formHtml((string) $page->getContent());
        $text = preg_replace('/\s+/', ' ', strip_tags($form)) ?? '';
        foreach (['Category ID', 'Last updated', 'Direct Products', 'Selected by Sites', 'Category Code', 'Category Icon', 'Upload Icon', 'Color', 'Published',
            'SEO', 'URL Template', 'Settings', 'Advanced', 'Duplicate', 'Delete', 'Import', 'Quality', 'General'] as $absent) {
            self::assertStringNotContainsString($absent, $text, $absent);
        }
        foreach (['name="status"', 'name="schema_status"', 'name="description"', 'name="position"', 'type="file"', 'role="tab"'] as $absent) {
            self::assertStringNotContainsString($absent, $form, $absent);
        }
    }

    public function test_store_creates_root_and_nested_draft_categories_appended_with_revision_and_audit(): void
    {
        $this->actingAs($this->admin);
        $rootRevision = app(CategoryHierarchy::class)->revision(null);
        $response = $this->post(route('central.categories.store'), ['name' => 'Cameras', 'slug' => 'cameras', 'parent_id' => '', 'scope_revision' => $rootRevision]);
        $root = CentralCategory::query()->where('slug', 'cameras')->sole();
        $response->assertRedirect(route('central.categories.show', $root))->assertSessionHas('success', 'Category #'.$root->id.' created as Draft.');
        self::assertNull($root->parent_id);
        self::assertSame(4, $root->position);
        self::assertSame(['draft', 'draft'], [$root->status->value, $root->schema_status->value]);
        self::assertSame($rootRevision + 1, app(CategoryHierarchy::class)->revision(null));

        $this->post(route('central.categories.store'), ['name' => 'Curved Monitors', 'slug' => 'curved-monitors', 'parent_id' => Fixture::DISPLAYS, 'scope_revision' => 1])->assertSessionHasNoErrors();
        $nested = CentralCategory::query()->where('slug', 'curved-monitors')->sole();
        self::assertSame([Fixture::DISPLAYS, 3], [$nested->parent_id, $nested->position]);
        self::assertSame(2, app(CategoryHierarchy::class)->revision(Fixture::DISPLAYS));
        self::assertSame(1, app(CategoryHierarchy::class)->revision(Fixture::ELECTRONICS));
        self::assertSame(2, AuditLogEntry::query()->where('action', 'catalog.category.created')->count());
        $this->get(route('central.categories.show', $nested))->assertOk()->assertSee('Curved Monitors')->assertSee('Draft');
        $this->get(route('central.categories.index', ['q' => 'Curved']))->assertOk()->assertSee('Curved Monitors');
    }

    /** @return iterable<string, array{array<string, mixed>, list<string>}> */
    public static function invalidCreates(): iterable
    {
        yield 'missing name' => [['name' => null], ['name']];
        yield 'empty name' => [['name' => '   '], ['name']];
        yield 'oversized name' => [['name' => str_repeat('n', 256)], ['name']];
        yield 'missing slug' => [['slug' => null], ['slug']];
        yield 'duplicate slug' => [['slug' => 'desktop-monitors'], ['slug']];
        yield 'oversized slug' => [['slug' => str_repeat('s', 256)], ['slug']];
        yield 'array name' => [['name' => ['x']], ['name']];
        yield 'array slug' => [['slug' => ['x']], ['slug']];
        yield 'unknown parent' => [['parent_id' => 99999999, 'scope_revision' => 0], ['parent_id']];
        yield 'non-integer parent' => [['parent_id' => 'displays'], ['parent_id']];
        yield 'stale scope revision' => [['scope_revision' => 0], ['hierarchy_revision']];
        yield 'malformed revision' => [['scope_revision' => 'one'], ['scope_revision']];
        yield 'negative revision' => [['scope_revision' => -1], ['scope_revision']];
        yield 'forged lifecycle' => [['status' => 'active', 'schema_status' => 'approved'], ['status', 'schema_status']];
        yield 'direct position' => [['position' => 0], ['position']];
    }

    /** @param array<string, mixed> $override
     * @param  list<string>  $errors
     */
    #[DataProvider('invalidCreates')]
    public function test_invalid_create_writes_nothing_and_keeps_safe_input(array $override, array $errors): void
    {
        $this->actingAs($this->admin);
        $before = $this->snapshot();
        $payload = array_filter([...['name' => 'Webcams', 'slug' => 'webcams', 'parent_id' => Fixture::ELECTRONICS, 'scope_revision' => 1], ...$override], static fn (mixed $value): bool => $value !== null);
        $this->from(route('central.categories.create'))->post(route('central.categories.store'), $payload)
            ->assertRedirect(route('central.categories.create'))->assertSessionHasErrors($errors);
        self::assertSame($before, $this->snapshot());
    }

    public function test_edit_renders_real_identity_placement_context_and_is_read_only(): void
    {
        $this->actingAs($this->admin);
        $before = $this->snapshot();
        $response = $this->get(route('central.categories.edit', Fixture::MONITORS))->assertOk()
            ->assertSee('data-screen-id="CA-018"', false)->assertSee('data-category-form-mode="edit"', false)
            ->assertSee('value="Monitors"', false)->assertSee('value="desktop-monitors"', false)
            ->assertSee('data-original-parent="'.Fixture::DISPLAYS.'"', false)
            ->assertSee('<option value="'.Fixture::DISPLAYS.'" selected>', false)
            ->assertSee('2 of 3 — current position')->assertSee('1 of 3 — before Televisions')->assertSee('3 of 3 — after Projectors')
            ->assertSee('name="current_scope_revision" value="1"', false)->assertSee('name="expected_slug" value="desktop-monitors"', false)
            ->assertSee('data-category-id>'.Fixture::MONITORS, false)->assertSee('data-summary="with-schema">Yes', false)
            ->assertSee('data-summary="sections">1', false)->assertSee('data-summary="products">3', false)->assertSee('data-summary="sites">2', false)
            ->assertSee('Active is canonical Category availability. Site selection and publication are managed separately.')
            ->assertSee('Category descriptions are localized and managed separately.')
            ->assertSee(route('central.categories.show', Fixture::MONITORS), false);
        // Self and descendants are not eligible parents; status is never an editable control.
        $response->assertDontSee('<option value="'.Fixture::MONITORS.'"', false)->assertDontSee('<option value="'.Fixture::GAMING_MONITORS.'"', false)
            ->assertDontSee('name="status"', false)->assertDontSee('name="schema_status"', false)->assertDontSee('Active categories are visible in your catalog');
        $this->get(route('central.categories.edit', [Fixture::MONITORS, 'slug_preview' => 'displays-monitors']))->assertOk()->assertSee('Routing impact of this slug change');
        $this->get(route('central.categories.slug-impact', [Fixture::MONITORS, 'slug' => 'monitor-screens']))->assertOk()->assertSee('monitor-screens');
        $this->get(route('central.categories.create'))->assertOk();
        self::assertSame($before, $this->snapshot());
    }

    public function test_unchanged_save_is_a_no_op_without_audit_or_revision_churn(): void
    {
        $this->actingAs($this->admin);
        $monitors = CentralCategory::query()->findOrFail(Fixture::MONITORS);
        $before = $this->snapshot();
        $this->patch(route('central.categories.update', $monitors), $this->categoryUpdatePayload($monitors))
            ->assertRedirect(route('central.categories.show', $monitors))->assertSessionHas('success', 'No changes to save.');
        self::assertSame($before, $this->snapshot());
    }

    public function test_name_only_change_preserves_placement_lifecycle_schema_products_sites_and_translations(): void
    {
        $this->actingAs($this->admin);
        $monitors = CentralCategory::query()->findOrFail(Fixture::MONITORS);
        $dependent = $this->dependentFacts();
        $this->patch(route('central.categories.update', $monitors), $this->categoryUpdatePayload($monitors, ['name' => 'Computer Monitors']))
            ->assertRedirect(route('central.categories.show', $monitors))->assertSessionHas('success', 'Category updated.');
        $fresh = $monitors->fresh();
        self::assertSame(['Computer Monitors', 'desktop-monitors', Fixture::DISPLAYS, 1, 'active', 'approved'], [$fresh->name, $fresh->slug, $fresh->parent_id, $fresh->position, $fresh->status->value, $fresh->schema_status->value]);
        self::assertSame($monitors->schema_revision, $fresh->schema_revision);
        self::assertSame($dependent, $this->dependentFacts());
        self::assertSame(1, app(CategoryHierarchy::class)->revision(Fixture::DISPLAYS));
        $audit = AuditLogEntry::query()->where('action', 'catalog.category.updated')->sole();
        self::assertSame(['name' => 'Monitors'], $audit->before_json);
        self::assertSame(['name' => 'Computer Monitors', 'changed_fields' => ['name']], $audit->after_json);
        $this->get(route('central.categories.show', $monitors))->assertSee('Computer Monitors')->assertSee('Source name or slug changed.');
    }

    public function test_parent_only_change_reparents_preserving_id_descendants_and_compacting_old_siblings(): void
    {
        $this->actingAs($this->admin);
        $monitors = CentralCategory::query()->findOrFail(Fixture::MONITORS);
        $rootRevision = app(CategoryHierarchy::class)->revision(null);
        $this->patch(route('central.categories.update', $monitors), $this->categoryUpdatePayload($monitors, ['parent_id' => Fixture::ELECTRONICS]))
            ->assertSessionHasNoErrors()->assertRedirect(route('central.categories.show', Fixture::MONITORS));
        self::assertSame([Fixture::ELECTRONICS, 3], [$monitors->fresh()->parent_id, $monitors->fresh()->position]);
        self::assertSame(Fixture::MONITORS, CentralCategory::query()->findOrFail(Fixture::GAMING_MONITORS)->parent_id);
        self::assertSame([Fixture::TELEVISIONS, Fixture::PROJECTORS], $this->order(Fixture::DISPLAYS));
        self::assertSame([Fixture::LAPTOPS, Fixture::DISPLAYS, Fixture::PHONES, Fixture::MONITORS], $this->order(Fixture::ELECTRONICS));
        self::assertSame([2, 2, $rootRevision, 1], [app(CategoryHierarchy::class)->revision(Fixture::DISPLAYS), app(CategoryHierarchy::class)->revision(Fixture::ELECTRONICS), app(CategoryHierarchy::class)->revision(null), app(CategoryHierarchy::class)->revision(Fixture::HOME_GARDEN)]);
        $audit = AuditLogEntry::query()->where('action', 'catalog.category.reparented')->latest('id')->firstOrFail();
        self::assertSame(Fixture::ELECTRONICS, $audit->after_json['parent_id']);
        self::assertSame(0, AuditLogEntry::query()->where('action', 'catalog.category.updated')->count());
        $this->get(route('central.categories.show', $monitors))->assertSee(route('central.categories.show', Fixture::ELECTRONICS), false);
    }

    public function test_display_position_change_saves_the_complete_sibling_order(): void
    {
        $this->actingAs($this->admin);
        $monitors = CentralCategory::query()->findOrFail(Fixture::MONITORS);
        $this->patch(route('central.categories.update', $monitors), $this->categoryUpdatePayload($monitors, ['display_position' => 0]))->assertSessionHasNoErrors();
        self::assertSame([Fixture::MONITORS, Fixture::TELEVISIONS, Fixture::PROJECTORS], $this->order(Fixture::DISPLAYS));
        self::assertSame(2, app(CategoryHierarchy::class)->revision(Fixture::DISPLAYS));
        $audit = AuditLogEntry::query()->where('action', 'catalog.category.reordered')->latest('id')->firstOrFail();
        self::assertSame([Fixture::MONITORS, Fixture::TELEVISIONS, Fixture::PROJECTORS], $audit->after_json['ordered_ids']);
    }

    public function test_combined_name_slug_and_parent_change_is_one_successful_save(): void
    {
        $this->actingAs($this->admin);
        $smartHome = CentralCategory::query()->findOrFail(Fixture::SMART_HOME);
        $this->patch(route('central.categories.update', $smartHome), $this->categoryUpdatePayload($smartHome, ['name' => 'Smart Living', 'slug' => 'smart-living', 'parent_id' => Fixture::HOME_GARDEN]))
            ->assertSessionHasNoErrors()->assertRedirect(route('central.categories.show', $smartHome));
        $fresh = $smartHome->fresh();
        self::assertSame(['Smart Living', 'smart-living', Fixture::HOME_GARDEN, 1, Fixture::SMART_HOME], [$fresh->name, $fresh->slug, $fresh->parent_id, $fresh->position, $fresh->id]);
        self::assertSame(1, AuditLogEntry::query()->where('action', 'catalog.category.reparented')->where('subject_id', (string) Fixture::SMART_HOME)->count());
        self::assertSame(1, AuditLogEntry::query()->where('action', 'catalog.category.updated')->count());
    }

    public function test_parent_and_display_order_in_one_save_are_rejected_without_writes(): void
    {
        $this->actingAs($this->admin);
        $monitors = CentralCategory::query()->findOrFail(Fixture::MONITORS);
        $before = $this->snapshot();
        $this->patch(route('central.categories.update', $monitors), $this->categoryUpdatePayload($monitors, ['parent_id' => Fixture::ELECTRONICS, 'display_position' => 0]))
            ->assertRedirect(route('central.categories.edit', $monitors))->assertSessionHasErrors(['display_position']);
        self::assertSame($before, $this->snapshot());
    }

    /** @return iterable<string, array{\Closure(array<string, mixed>): array<string, mixed>, list<string>}> */
    public static function invalidEdits(): iterable
    {
        yield 'self parent' => [fn (array $p): array => [...$p, 'parent_id' => Fixture::MONITORS, 'destination_scope_revision' => 0], ['parent_id']];
        yield 'descendant parent' => [fn (array $p): array => [...$p, 'parent_id' => Fixture::GAMING_MONITORS, 'destination_scope_revision' => 0], ['parent_id']];
        yield 'unknown parent' => [fn (array $p): array => [...$p, 'parent_id' => 99999999, 'destination_scope_revision' => 0], ['parent_id']];
        yield 'non-integer parent' => [fn (array $p): array => [...$p, 'parent_id' => 'root'], ['parent_id']];
        yield 'stale old scope revision' => [fn (array $p): array => [...$p, 'parent_id' => Fixture::ELECTRONICS, 'current_scope_revision' => 0], ['hierarchy_revision']];
        yield 'stale destination revision' => [fn (array $p): array => [...$p, 'parent_id' => Fixture::ELECTRONICS, 'destination_scope_revision' => 0], ['hierarchy_revision']];
        yield 'stale order revision' => [fn (array $p): array => [...$p, 'display_position' => 0, 'current_scope_revision' => 0], ['hierarchy_revision']];
        yield 'duplicate sibling ids' => [fn (array $p): array => [...$p, 'sibling_ids' => [Fixture::TELEVISIONS, Fixture::MONITORS, Fixture::MONITORS], 'display_position' => 0], ['sibling_ids.1', 'sibling_ids.2']];
        yield 'missing sibling id' => [fn (array $p): array => [...$p, 'sibling_ids' => [Fixture::TELEVISIONS, Fixture::MONITORS], 'display_position' => 0], ['ordered_ids']];
        yield 'foreign sibling id' => [fn (array $p): array => [...$p, 'sibling_ids' => [Fixture::TELEVISIONS, Fixture::MONITORS, Fixture::LAPTOPS], 'display_position' => 0], ['ordered_ids']];
        yield 'out of range position' => [fn (array $p): array => [...$p, 'display_position' => 3], ['display_position']];
        yield 'category absent from snapshot' => [fn (array $p): array => [...$p, 'sibling_ids' => [Fixture::TELEVISIONS, Fixture::PROJECTORS]], ['display_position']];
        yield 'moved since load' => [fn (array $p): array => [...$p, 'expected_parent_id' => Fixture::ELECTRONICS, 'parent_id' => Fixture::ELECTRONICS], ['parent_id']];
        yield 'identity changed since load' => [fn (array $p): array => [...$p, 'expected_name' => 'Displays & Monitors'], ['category']];
        yield 'forged lifecycle and schema' => [fn (array $p): array => [...$p, 'status' => 'archived', 'schema_status' => 'draft', 'schema_revision' => 9], ['status', 'schema_status', 'schema_revision']];
        yield 'direct position tampering' => [fn (array $p): array => [...$p, 'position' => 0], ['position']];
        yield 'malformed tokens' => [fn (array $p): array => [...$p, 'current_scope_revision' => 'x', 'destination_scope_revision' => [1], 'sibling_ids' => 'all', 'slug_impact_fingerprint' => 'abc'], ['current_scope_revision', 'destination_scope_revision', 'sibling_ids', 'slug_impact_fingerprint']];
        yield 'arrays in scalars' => [fn (array $p): array => [...$p, 'name' => ['Monitors'], 'slug' => ['desktop-monitors'], 'expected_slug' => ['desktop-monitors']], ['name', 'slug', 'expected_slug']];
        yield 'missing tokens' => [fn (array $p): array => array_diff_key($p, array_flip(['expected_name', 'expected_slug', 'current_scope_revision', 'display_position'])), ['expected_name', 'expected_slug', 'current_scope_revision', 'display_position']];
        yield 'empty name' => [fn (array $p): array => [...$p, 'name' => ''], ['name']];
        yield 'duplicate slug' => [fn (array $p): array => [...$p, 'slug' => 'tvs'], ['slug_impact']];
    }

    /** @param \Closure(array<string, mixed>): array<string, mixed> $mutate
     * @param  list<string>  $errors
     */
    #[DataProvider('invalidEdits')]
    public function test_invalid_or_stale_edits_change_nothing(\Closure $mutate, array $errors): void
    {
        $this->actingAs($this->admin);
        $monitors = CentralCategory::query()->findOrFail(Fixture::MONITORS);
        $payload = $mutate($this->categoryUpdatePayload($monitors));
        $before = $this->snapshot();
        $this->from(route('central.categories.edit', $monitors))->patch(route('central.categories.update', $monitors), $payload)
            ->assertSessionHasErrors($errors);
        self::assertSame($before, $this->snapshot());
    }

    public function test_duplicate_slug_with_a_reviewed_impact_is_rejected_by_the_canonical_unique_rule(): void
    {
        $this->actingAs($this->admin);
        $smartHome = CentralCategory::query()->findOrFail(Fixture::SMART_HOME);
        $before = $this->snapshot();
        $this->patch(route('central.categories.update', $smartHome), $this->categoryUpdatePayload($smartHome, ['slug' => 'desktop-monitors']))
            ->assertRedirect(route('central.categories.edit', [$smartHome, 'slug_preview' => 'desktop-monitors']))->assertSessionHasErrors(['slug']);
        self::assertSame($before, $this->snapshot());
    }

    public function test_audit_failure_rolls_back_every_part_of_a_composite_save(): void
    {
        $this->actingAs($this->admin);
        $monitors = CentralCategory::query()->findOrFail(Fixture::MONITORS);
        $payload = $this->categoryUpdatePayload($monitors, ['name' => 'Screens', 'parent_id' => Fixture::ELECTRONICS]);
        $before = $this->snapshot();
        $real = app(AuditRecorder::class);
        // The reparent audit succeeds; the identity audit fails after the reparent already ran.
        $audit = Mockery::mock(AuditRecorder::class);
        $audit->shouldReceive('record')->andReturnUsing(function (mixed ...$arguments) use ($real): mixed {
            if ($arguments[0] === AuditAction::CatalogCategoryUpdated) {
                throw new RuntimeException('audit unavailable');
            }

            return $real->record(...$arguments);
        });
        $this->app->instance(AuditRecorder::class, $audit);
        $this->withoutExceptionHandling();
        try {
            $this->patch(route('central.categories.update', $monitors), $payload);
            self::fail('Audit failure should abort the save.');
        } catch (RuntimeException $exception) {
            self::assertSame('audit unavailable', $exception->getMessage());
        }
        self::assertSame($before, $this->snapshot());
    }

    public function test_cancel_and_back_links_target_detail_or_list_without_writes(): void
    {
        $this->actingAs($this->admin);
        $this->get(route('central.categories.edit', Fixture::MONITORS))
            ->assertSee('href="'.route('central.categories.show', Fixture::MONITORS).'"', false)->assertSee('data-category-form-cancel', false)
            ->assertSee('Back to Category Detail');
        $create = $this->formHtml((string) $this->get(route('central.categories.create'))->getContent());
        self::assertMatchesRegularExpression('/<a\s+href="'.preg_quote(route('central.categories.index'), '/').'"[^>]*data-category-form-cancel/', $create);
        $edit = $this->formHtml((string) $this->get(route('central.categories.edit', Fixture::MONITORS))->getContent());
        self::assertMatchesRegularExpression('/<a\s+href="'.preg_quote(route('central.categories.show', Fixture::MONITORS), '/').'"[^>]*data-category-form-cancel/', $edit);
    }

    public function test_archived_category_keeps_its_retained_data_and_can_change_identity(): void
    {
        $this->actingAs($this->admin);
        $legacy = CentralCategory::query()->findOrFail(Fixture::LEGACY);
        $this->get(route('central.categories.edit', $legacy))->assertOk()->assertSee('Archived')->assertSee('data-summary="products">1', false)->assertSee('data-summary="sites">1', false);
        $this->patch(route('central.categories.update', $legacy), $this->categoryUpdatePayload($legacy, ['name' => 'Legacy Input Devices']))->assertSessionHasNoErrors();
        self::assertSame(['Legacy Input Devices', 'archived'], [$legacy->fresh()->name, $legacy->fresh()->status->value]);
        self::assertSame(1, $legacy->products()->count());
        self::assertSame(1, SiteCategory::query()->where('central_category_id', Fixture::LEGACY)->count());
    }

    public function test_validation_failure_keeps_safe_input_but_never_shows_an_ineligible_parent_as_selected(): void
    {
        $this->actingAs($this->admin);
        $monitors = CentralCategory::query()->findOrFail(Fixture::MONITORS);
        $this->from(route('central.categories.edit', $monitors))->followingRedirects()
            ->patch(route('central.categories.update', $monitors), [...$this->categoryUpdatePayload($monitors), 'name' => 'Kept Name', 'parent_id' => Fixture::GAMING_MONITORS, 'destination_scope_revision' => 0])
            ->assertOk()->assertSee('value="Kept Name"', false)->assertSee('Category ancestry must be acyclic.')
            ->assertSee('<option value="'.Fixture::DISPLAYS.'" selected>', false)->assertDontSee('<option value="'.Fixture::GAMING_MONITORS.'"', false);
        $this->from(route('central.categories.create'))->followingRedirects()
            ->post(route('central.categories.store'), ['name' => 'Typed', 'slug' => 'desktop-monitors', 'parent_id' => 99999999, 'scope_revision' => 0])
            ->assertOk()->assertSee('value="Typed"', false)->assertDontSee('selected>', false);
    }

    /** @return iterable<string, array{\Closure(): User, bool}> */
    public static function actors(): iterable
    {
        yield 'disabled admin' => [fn (): User => User::factory()->centralAdmin()->disabled()->create(), false];
        yield 'site admin' => [fn (): User => User::factory()->create(['role' => UserRole::SiteAdmin]), false];
        yield 'moderator' => [fn (): User => User::factory()->create(['role' => UserRole::Moderator]), false];
        yield 'translator' => [fn (): User => User::factory()->create(['role' => UserRole::Translator]), false];
        yield 'schema only' => [fn (): User => self::catalogEditor(['central.panel.access', 'central.page.access', 'central.mutation.execute', 'catalog.schema.manage']), false];
        yield 'product only' => [fn (): User => self::catalogEditor(['central.panel.access', 'central.page.access', 'central.mutation.execute', 'catalog.products.manage']), false];
        yield 'brand only' => [fn (): User => self::catalogEditor(['central.panel.access', 'central.page.access', 'central.mutation.execute', 'catalog.brands.manage']), false];
        yield 'category reader without mutation' => [fn (): User => self::catalogEditor(['central.panel.access', 'central.page.access', 'catalog.categories.manage']), false];
        yield 'category actor without panel' => [fn (): User => self::catalogEditor(['central.page.access', 'central.mutation.execute', 'catalog.categories.manage']), false];
        yield 'category actor without page' => [fn (): User => self::catalogEditor(['central.panel.access', 'central.mutation.execute', 'catalog.categories.manage']), false];
        yield 'catalog editor' => [fn (): User => User::factory()->create(['role' => UserRole::CatalogEditor]), true];
        yield 'central admin' => [fn (): User => User::factory()->centralAdmin()->create(), true];
    }

    /** @param \Closure(): User $actor */
    #[DataProvider('actors')]
    public function test_form_reads_and_writes_require_category_capability_with_central_mutation(\Closure $actor, bool $allowed): void
    {
        $user = $actor();
        $monitors = CentralCategory::query()->findOrFail(Fixture::MONITORS);
        $payload = $this->categoryUpdatePayload($monitors, ['name' => 'Forged']);
        $before = $this->snapshot();
        $this->actingAs($user);
        $requests = [
            fn () => $this->get(route('central.categories.create')),
            fn () => $this->get(route('central.categories.edit', $monitors)),
            fn () => $this->get(route('central.categories.slug-impact', [$monitors, 'slug' => 'x'])),
            fn () => $this->post(route('central.categories.store'), ['name' => 'Forged', 'slug' => 'forged', 'scope_revision' => 1]),
            fn () => $this->patch(route('central.categories.update', $monitors), $payload),
        ];
        foreach ($requests as $request) {
            $response = $request();
            if ($allowed) {
                self::assertContains($response->getStatusCode(), [200, 302]);
            } else {
                self::assertContains($response->getStatusCode(), [302, 403]);
                if ($response->getStatusCode() === 302) {
                    self::assertStringContainsString('/login', (string) $response->headers->get('Location'));
                }
            }
        }
        if (! $allowed) {
            self::assertSame($before, $this->snapshot());
            self::assertFalse(User::query()->whereKey($user->id)->sole()->can('catalog.categories.manage', [true]));
        }
    }

    public function test_guests_are_redirected_to_login_without_writes(): void
    {
        $monitors = CentralCategory::query()->findOrFail(Fixture::MONITORS);
        $before = $this->snapshot();
        $this->get(route('central.categories.create'))->assertRedirect(route('filament.central.auth.login'));
        $this->get(route('central.categories.edit', $monitors))->assertRedirect(route('filament.central.auth.login'));
        $this->post(route('central.categories.store'), ['name' => 'Guest', 'slug' => 'guest', 'scope_revision' => 1])->assertRedirect(route('filament.central.auth.login'));
        $this->patch(route('central.categories.update', $monitors), ['name' => 'Guest'])->assertRedirect(route('filament.central.auth.login'));
        self::assertSame($before, $this->snapshot());
    }

    public function test_routes_are_named_numeric_and_guarded_by_category_capability_and_central_mutation(): void
    {
        foreach (['central.categories.create', 'central.categories.store', 'central.categories.edit', 'central.categories.update', 'central.categories.slug-impact'] as $name) {
            $middleware = Route::getRoutes()->getByName($name)?->gatherMiddleware() ?? [];
            self::assertContains('can:catalog.categories.manage', $middleware, $name);
            self::assertContains('can:central.mutation.execute', $middleware, $name);
        }
        self::assertSame('/admin/central/categories/create', route('central.categories.create', absolute: false));
        self::assertSame('/admin/central/categories/'.Fixture::MONITORS.'/edit', route('central.categories.edit', Fixture::MONITORS, false));
        $this->actingAs($this->admin);
        $this->get('/admin/central/categories/create')->assertOk()->assertSee('data-category-form-mode="create"', false);
        $this->get('/admin/central/categories/desktop-monitors/edit')->assertNotFound();
        $this->get('/admin/central/categories/99999999/edit')->assertNotFound();
        $this->patch('/admin/central/categories/create', ['name' => 'x'])->assertStatus(405);
        $this->get('/admin/central/categories/'.Fixture::MONITORS)->assertOk()->assertSee('data-screen-id="CA-017"', false);
        $this->get(route('central.categories.index'))->assertOk()
            ->assertSee('href="'.route('central.categories.create').'"', false)
            ->assertSee(route('central.categories.edit', Fixture::MONITORS), false);
        $this->get(route('central.categories.show', Fixture::MONITORS))->assertSee('href="'.route('central.categories.edit', Fixture::MONITORS).'"', false);
    }

    private function formHtml(string $html): string
    {
        $marker = strpos($html, 'id="category-form"');
        self::assertNotFalse($marker);
        $start = strrpos(substr($html, 0, $marker), '<form');
        self::assertNotFalse($start);
        $end = strpos($html, '</form>', $start);
        self::assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }

    /** @param list<string> $permissions */
    private static function catalogEditor(array $permissions): User
    {
        config(['cataloghub_permissions.roles.catalog_editor' => $permissions]);

        return User::factory()->create(['role' => UserRole::CatalogEditor]);
    }

    /** @return list<int> */
    private function order(int $parentId): array
    {
        return CentralCategory::query()->where('parent_id', $parentId)->orderBy('position')->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    /** @return array<string, mixed> */
    private function dependentFacts(): array
    {
        $rows = fn (string $table, string $column): string => DB::table($table)->where($column, Fixture::MONITORS)->orderBy('id')->get()->toJson();

        return [
            'products' => $rows('central_products', 'central_category_id'),
            'sites' => $rows('site_categories', 'central_category_id'),
            'translations' => $rows('category_translations', 'category_id'),
            'projections' => $rows('site_category_projections', 'central_category_id'),
            'assignments' => $rows('category_attribute_assignments', 'central_category_id'),
            'children' => $this->order(Fixture::MONITORS),
        ];
    }

    /** @return array<string, list<string>> */
    private function snapshot(): array
    {
        $snapshot = [];
        foreach (Schema::getTableListing() as $table) {
            if (in_array($table, ['migrations', 'sessions', 'cache', 'cache_locks'], true)) {
                continue;
            }
            $rows = DB::table($table)->get()->map(fn ($row): string => json_encode($row, JSON_THROW_ON_ERROR))->all();
            sort($rows);
            $snapshot[$table] = $rows;
        }

        return $snapshot;
    }
}
