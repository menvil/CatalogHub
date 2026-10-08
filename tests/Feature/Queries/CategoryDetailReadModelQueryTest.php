<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Enums\AuditAction;
use App\Enums\TranslationStatus;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\FacetDefinition;
use App\Models\Locale;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\Translations\CategoryTranslation;
use App\Models\User;
use App\Queries\CentralCatalog\CategoryActivityQuery;
use App\Queries\CentralCatalog\CategoryDetailReadModelQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Support\CategoryDetailFixture;
use Tests\TestCase;

final class CategoryDetailReadModelQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_local_schema_and_direct_usage_counts_with_real_approval_attribution(): void
    {
        $category = CategoryDetailFixture::create();
        $detail = app(CategoryDetailReadModelQuery::class)->forCategory($category);
        self::assertSame([2, 4, 2, 1, 2], [$detail->schema->sections, $detail->schema->attributes, $detail->schema->required, $detail->schema->facets, $detail->schema->comparison]);
        self::assertSame(4, $detail->products);
        self::assertSame(3, $detail->selectedSites);
        self::assertSame(['Central Store', 'Preview Store', 'Suspended Store'], array_column($detail->sites, 'name'));
        self::assertSame(['Electronics', 'Displays'], array_column($detail->ancestors, 'name'));
        self::assertTrue($detail->hierarchyAvailable);
        self::assertSame('Sam Schema', $detail->schema->reviewer);
        self::assertSame('Sam Schema', $detail->schema->approver);
        self::assertSame(6, $category->schema_revision);
        self::assertSame($category->schema_revision, $category->schema_approved_revision);
        self::assertSame(0, $detail->schema->issueCount);
        self::assertSame('Ada Catalog', $detail->activity->createdBy);
        self::assertSame('Not recorded', $detail->activity->lastIdentityUpdateBy);
    }

    public function test_active_locale_coverage_exact_status_and_description_fallback_remain_separate(): void
    {
        $category = CategoryDetailFixture::create();
        $locale = Locale::query()->where('code', 'de-DE')->firstOrFail();
        $detail = app(CategoryDetailReadModelQuery::class)->forCategory($category, $locale->id);
        $summary = $detail->translations;
        self::assertSame([5, 3, 1, 1], [$summary->total(), $summary->covered, $summary->missing, $summary->outdated]);
        self::assertSame(60.0, $summary->percentage());
        self::assertSame(TranslationStatus::Missing, $summary->status);
        self::assertSame('en-US', $summary->description->locale);
        self::assertSame('fallback_locale', $summary->description->source);
        self::assertSame(TranslationStatus::Approved, $summary->description->status);
        self::assertStringContainsString('High performance', $summary->description->value);
        self::assertFalse(CategoryTranslation::query()->where('category_id', $category->id)->where('locale_id', $locale->id)->exists());
        $outdated = app(CategoryDetailReadModelQuery::class)->forCategory($category, Locale::query()->where('code', 'ja-JP')->value('id'));
        self::assertSame(TranslationStatus::Outdated, $outdated->translations->status);
    }

    public function test_default_then_first_active_locale_then_no_locales_is_deterministic_and_neutral(): void
    {
        $category = CentralCategory::factory()->create();
        $b = Locale::factory()->create(['code' => 'bb-BB', 'is_active' => true, 'is_default' => false, 'position' => 1]);
        $a = Locale::factory()->create(['code' => 'aa-AA', 'is_active' => true, 'is_default' => false, 'position' => 0]);
        $query = app(CategoryDetailReadModelQuery::class);
        self::assertSame($a->id, $query->forCategory($category)->translations->localeId);
        $b->forceFill(['is_default' => true])->saveOrFail();
        self::assertSame($b->id, $query->forCategory($category)->translations->localeId);
        Locale::query()->update(['is_active' => false]);
        $empty = $query->forCategory($category)->translations;
        self::assertNull($empty->localeId);
        self::assertNull($empty->description);
        self::assertNull($empty->percentage());
        self::assertSame(0, $empty->missing);
    }

    public function test_a_locale_that_becomes_inactive_cannot_reach_the_read_model(): void
    {
        $category = CentralCategory::factory()->create();
        $locale = Locale::factory()->create(['is_active' => true]);
        $locale->update(['is_active' => false]);
        $this->expectException(ValidationException::class);
        app(CategoryDetailReadModelQuery::class)->forCategory($category, $locale->id);
    }

    public function test_corrupt_cycles_and_missing_parents_are_unavailable_without_repair(): void
    {
        $category = CentralCategory::factory()->create();
        $parent = CentralCategory::factory()->create(['parent_id' => $category->id]);
        $category->update(['parent_id' => $parent->id]);
        $detail = app(CategoryDetailReadModelQuery::class)->forCategory($category->refresh());
        self::assertFalse($detail->hierarchyAvailable);
        self::assertSame([], $detail->ancestors);
        self::assertSame($parent->id, $category->fresh()->parent_id);
        // A stale historical snapshot can refer to a missing parent even when current FKs are sound.
        $category->setAttribute('parent_id', 99999999);
        self::assertFalse(app(CategoryDetailReadModelQuery::class)->forCategory($category)->hierarchyAvailable);
    }

    public function test_activity_scope_order_limit_and_allowlisted_summaries_do_not_leak_other_domains(): void
    {
        $category = CentralCategory::factory()->create();
        $other = CentralCategory::factory()->create();
        $definition = AttributeDefinition::factory()->create();
        CategoryAttributeAssignment::factory()->create(['central_category_id' => $category->id, 'attribute_definition_id' => $definition->id]);
        $actor = User::factory()->centralAdmin()->create();
        $rows = [];
        for ($i = 0; $i < 50; $i++) {
            $rows[] = AuditLogEntry::factory()->create(['context' => 'central', 'site_id' => null, 'subject_type' => $category->getMorphClass(), 'subject_id' => (string) $category->id, 'action' => AuditAction::CatalogCategoryUpdated->value, 'actor_id' => $actor->id, 'after_json' => ['name' => 'Changed '.$i, 'secret' => 'DO-NOT-RENDER', 'email' => 'private@fixture.test'], 'created_at' => '2026-10-06 10:00:00']);
        }
        foreach ([[$other->getMorphClass(), $other->id, AuditAction::CatalogCategoryUpdated->value], [$definition->getMorphClass(), $definition->id, AuditAction::CatalogAttributeUpdated->value], [$category->getMorphClass(), $category->id, AuditAction::Login->value]] as [$type, $id, $action]) {
            AuditLogEntry::factory()->create(['context' => 'central', 'site_id' => null, 'subject_type' => $type, 'subject_id' => (string) $id, 'action' => $action, 'created_at' => '2026-10-07 10:00:00']);
        }
        $activity = app(CategoryActivityQuery::class)->forCategory($category);
        self::assertCount(12, $activity->events);
        self::assertSame(array_reverse(array_map(fn ($row) => $row->id, array_slice($rows, -12))), array_column($activity->events, 'id'));
        self::assertSame('Not recorded', $activity->createdBy);
        self::assertSame($actor->name, $activity->lastIdentityUpdateBy);
        foreach ($activity->events as $event) {
            self::assertSame('Source name or slug changed.', $event->summary);
        }
    }

    public function test_missing_audit_actor_is_safe_and_schema_changes_do_not_claim_identity_update(): void
    {
        $category = CentralCategory::factory()->create();
        AuditLogEntry::factory()->create(['context' => 'central', 'site_id' => null, 'subject_type' => $category->getMorphClass(), 'subject_id' => (string) $category->id, 'action' => AuditAction::CatalogCategorySchemaReviewed->value, 'actor_id' => null]);
        $activity = app(CategoryActivityQuery::class)->forCategory($category);
        self::assertSame('Not recorded', $activity->lastIdentityUpdateBy);
        self::assertSame('Not recorded', $activity->events[0]->actor);
    }

    public function test_empty_sections_show_existing_validator_issues_without_changing_schema_state(): void
    {
        $category = CentralCategory::factory()->create(['schema_status' => 'draft']);
        AttributeSection::factory()->create(['central_category_id' => $category->id]);
        $detail = app(CategoryDetailReadModelQuery::class)->forCategory($category);
        self::assertSame(1, $detail->schema->issueCount);
        self::assertSame('empty_section', $detail->schema->issues[0]->code);
        self::assertSame('draft', $category->fresh()->schema_status->value);
    }

    public function test_query_count_stays_bounded_as_products_schema_locales_sites_and_history_grow(): void
    {
        $category = CentralCategory::factory()->create();
        $locale = Locale::factory()->create(['code' => 'en-US', 'is_default' => true, 'is_active' => true]);
        CentralProduct::factory()->create(['central_category_id' => $category->id]);
        $site = Site::factory()->create(['status' => 'active']);
        SiteCategory::query()->create(['site_id' => $site->id, 'central_category_id' => $category->id]);
        AuditLogEntry::factory()->create(['context' => 'central', 'site_id' => null, 'subject_type' => $category->getMorphClass(), 'subject_id' => (string) $category->id, 'action' => AuditAction::CatalogCategoryActivated->value]);
        $small = $this->queryCount($category);
        CentralProduct::factory()->count(30)->create(['central_category_id' => $category->id]);
        Locale::factory()->count(8)->create(['is_active' => true]);
        for ($i = 0; $i < 25; $i++) {
            $section = AttributeSection::factory()->create(['central_category_id' => $category->id]);
            CategoryAttributeAssignment::factory()->create(['central_category_id' => $category->id, 'attribute_section_id' => $section->id]);
        }
        for ($i = 0; $i < 10; $i++) {
            $site = Site::factory()->create(['status' => 'active']);
            SiteCategory::query()->create(['site_id' => $site->id, 'central_category_id' => $category->id]);
        }
        AuditLogEntry::factory()->count(55)->create(['context' => 'central', 'site_id' => null, 'subject_type' => $category->getMorphClass(), 'subject_id' => (string) $category->id, 'action' => AuditAction::CatalogCategoryUpdated->value]);
        FacetDefinition::factory()->count(10)->create(['category_id' => $category->id]);
        $large = $this->queryCount($category);
        self::assertLessThanOrEqual($small + 8, $large);
        self::assertLessThanOrEqual(30, $large);
        $detail = app(CategoryDetailReadModelQuery::class)->forCategory($category, $locale->id);
        self::assertCount(8, $detail->sites);
        self::assertCount(12, $detail->activity->events);
        self::assertSame(31, $detail->products);
        self::assertSame(11, $detail->selectedSites);
        fwrite(STDOUT, sprintf("CA-017 query count: small=%d large=%d\n", $small, $large));
    }

    private function queryCount(CentralCategory $category): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        app(CategoryDetailReadModelQuery::class)->forCategory($category->fresh());
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
