<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Actions\CategorySchema\ApproveCategorySchemaAction;
use App\Actions\CategorySchema\AssignAttributeToCategoryAction;
use App\Actions\CategorySchema\MarkCategorySchemaReviewedAction;
use App\Actions\CentralCatalog\ActivateCentralCategoryAction;
use App\Actions\CentralCatalog\ArchiveCentralCategoryAction;
use App\Actions\CentralCatalog\ReorderCentralCategoriesAction;
use App\Actions\CentralCatalog\ReparentCentralCategoryAction;
use App\Actions\Sites\UpsertSiteOverrideAction;
use App\Actions\Translations\SaveCategoryTranslationAction;
use App\Domains\Projections\SiteSyncService;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\User;
use App\Services\Categories\CategoryHierarchy;
use Illuminate\Support\Carbon;

/**
 * `category-create-edit-v1`: deterministic CA-018 Create/Edit state.
 *
 * Hierarchy (stable IDs; three-plus levels):
 *   Electronics 196001 → Laptops 196005, Displays 196006, Phones 196007 (Draft)
 *     Displays → Televisions 196008, Monitors 196009 (primary), Projectors 196010 (Draft)
 *       Monitors → Gaming Monitors 196011 (Draft descendant, forbidden as Monitors' parent)
 *   Home & Garden 196002 (alternative parent) → Garden Tools 196012
 *   Legacy Peripherals 196003 (Archived; retains a Product and a Site selection)
 *   Smart Home 196004 (Draft, no dependent data)
 *
 * Real reorder/reparent actions give the Electronics, Displays, Home & Garden and
 * root scopes revision 1. Monitors is Active with an Approved schema, three
 * direct Products, en-US/de-DE translations and selections by Display World
 * (Active) and Preview Outlet (Draft). Display World's projections are built by
 * the real SiteSyncService; its de-DE projection keeps the `bildschirme` Site
 * override. No public URL or projection state is fabricated.
 */
final class CategoryCreateEditFixture
{
    public const VERSION = 'category-create-edit-v1';

    public const ELECTRONICS = 196001;

    public const HOME_GARDEN = 196002;

    public const LEGACY = 196003;

    public const SMART_HOME = 196004;

    public const LAPTOPS = 196005;

    public const DISPLAYS = 196006;

    public const PHONES = 196007;

    public const TELEVISIONS = 196008;

    public const MONITORS = 196009;

    public const PROJECTORS = 196010;

    public const GAMING_MONITORS = 196011;

    public const GARDEN_TOOLS = 196012;

    public const DISPLAY_WORLD = 196101;

    public const PREVIEW_OUTLET = 196102;

    public static function create(): CentralCategory
    {
        $previous = Carbon::getTestNow();
        Carbon::setTestNow('2026-10-06T09:00:00Z');
        try {
            $actor = User::factory()->centralAdmin()->create(['name' => 'Ada Catalog', 'email' => 'ada-ca018@fixture.test']);
            $category = static fn (int $id, string $name, string $slug, ?int $parent, int $position, string $status = 'active'): CentralCategory => CentralCategory::factory()->create([
                'id' => $id, 'name' => $name, 'slug' => $slug, 'parent_id' => $parent, 'position' => $position, 'status' => $status, 'schema_status' => 'draft',
            ]);
            // The browser harness also holds factory-made registry/Brand roots with legacy,
            // non-contiguous positions. The explicit complete-set reorder is the only repair
            // path (a no-op when already contiguous); this fixture's roots are then appended.
            $existingRoots = CentralCategory::query()->whereNull('parent_id')->orderBy('position')->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
            app(ReorderCentralCategoriesAction::class)->handle($actor, null, $existingRoots, app(CategoryHierarchy::class)->revision(null));
            $root = count($existingRoots);
            $category(self::ELECTRONICS, 'Electronics', 'electronics', null, $root);
            $category(self::HOME_GARDEN, 'Home & Garden', 'home-garden', null, $root + 1);
            $legacy = $category(self::LEGACY, 'Legacy Peripherals', 'legacy-peripherals', null, $root + 2);
            $category(self::SMART_HOME, 'Smart Home', 'smart-home', null, $root + 3, 'draft');
            $gardenTools = $category(self::GARDEN_TOOLS, 'Garden Tools', 'garden-tools', null, $root + 4);
            $category(self::DISPLAYS, 'Displays', 'displays', self::ELECTRONICS, 0);
            $category(self::LAPTOPS, 'Laptops', 'notebooks', self::ELECTRONICS, 1);
            $category(self::PHONES, 'Phones', 'mobile-phones', self::ELECTRONICS, 2, 'draft');
            $monitors = $category(self::MONITORS, 'Monitors', 'desktop-monitors', self::DISPLAYS, 0, 'draft');
            $category(self::TELEVISIONS, 'Televisions', 'tvs', self::DISPLAYS, 1);
            $category(self::PROJECTORS, 'Projectors', 'projectors', self::DISPLAYS, 2, 'draft');
            $category(self::GAMING_MONITORS, 'Gaming Monitors', 'gaming-monitors', self::MONITORS, 0, 'draft');

            // Genuine structural history: each action increments only its own scope revisions.
            Carbon::setTestNow('2026-10-06T10:00:00Z');
            app(ReorderCentralCategoriesAction::class)->handle($actor, self::ELECTRONICS, [self::LAPTOPS, self::DISPLAYS, self::PHONES], 0);
            app(ReorderCentralCategoriesAction::class)->handle($actor, self::DISPLAYS, [self::TELEVISIONS, self::MONITORS, self::PROJECTORS], 0);
            app(ReparentCentralCategoryAction::class)->handle($actor, $gardenTools, self::HOME_GARDEN, app(CategoryHierarchy::class)->revision(null), 0);

            Carbon::setTestNow('2026-10-07T09:00:00Z');
            $section = AttributeSection::factory()->create(['central_category_id' => self::MONITORS, 'code' => 'display', 'name' => 'Display', 'position' => 0]);
            foreach (['Refresh rate' => 'integer', 'Panel type' => 'string'] as $name => $type) {
                $definition = AttributeDefinition::factory()->create(['code' => 'ca018_'.str($name)->snake(), 'name' => $name, 'data_type' => $type, 'measurement_dimension_id' => null, 'canonical_measurement_unit_id' => null]);
                app(AssignAttributeToCategoryAction::class)->handle($monitors->refresh(), $definition, ['attribute_section_id' => $section->id, 'position' => $type === 'integer' ? 0 : 1, 'is_required' => true, 'is_visible' => true, 'is_searchable' => false, 'is_sortable' => false], $monitors->refresh()->schema_revision, $actor);
            }
            app(MarkCategorySchemaReviewedAction::class)->handle($monitors->refresh(), $monitors->schema_revision, $actor);
            app(ApproveCategorySchemaAction::class)->handle($monitors->refresh(), $monitors->refresh()->schema_revision, $actor);
            app(ActivateCentralCategoryAction::class)->handle($actor, $monitors->refresh());
            foreach ([1, 2, 3] as $number) {
                CentralProduct::factory()->create(['central_category_id' => self::MONITORS, 'name' => 'Studio Monitor '.$number, 'slug' => 'ca018-monitor-'.$number, 'status' => 'active']);
            }
            CentralProduct::factory()->create(['central_category_id' => self::LEGACY, 'name' => 'Retained legacy keyboard', 'slug' => 'ca018-legacy-keyboard', 'status' => 'active']);

            Locale::query()->update(['is_active' => false]);
            $locales = [];
            foreach (['en-US' => 'en', 'de-DE' => 'de'] as $code => $language) {
                $locale = Locale::query()->firstOrNew(['code' => $code]);
                $locale->forceFill(['language_code' => $language, 'region_code' => substr($code, 3), 'name' => $code, 'native_name' => $code, 'direction' => 'ltr', 'is_active' => true, 'is_default' => $code === 'en-US', 'position' => count($locales)])->saveOrFail();
                $locales[$code] = $locale;
            }
            app(SaveCategoryTranslationAction::class)->handle($monitors->refresh(), $locales['en-US'], ['name' => 'Monitors', 'description' => 'Desktop and professional monitors.', 'status' => TranslationStatus::HumanReviewed]);
            app(SaveCategoryTranslationAction::class)->handle($monitors->refresh(), $locales['de-DE'], ['name' => 'Bildschirme', 'description' => 'Desktop- und Profi-Bildschirme.', 'status' => TranslationStatus::MachineTranslated]);

            Carbon::setTestNow('2026-10-08T09:00:00Z');
            $live = Site::factory()->withRuntimeContext(['en-US', 'de-DE'])->create(['id' => self::DISPLAY_WORLD, 'name' => 'Display World', 'code' => 'ca018-display-world', 'domain' => 'display-world.test', 'status' => 'active']);
            $preview = Site::factory()->withRuntimeContext(['en-US'])->create(['id' => self::PREVIEW_OUTLET, 'name' => 'Preview Outlet', 'code' => 'ca018-preview-outlet', 'domain' => 'preview-outlet.test', 'status' => 'draft']);
            foreach ([$live, $preview] as $site) {
                SiteCategory::query()->create(['site_id' => $site->id, 'central_category_id' => self::MONITORS, 'is_enabled' => true]);
            }
            SiteCategory::query()->create(['site_id' => $live->id, 'central_category_id' => self::LEGACY, 'is_enabled' => false]);
            app(UpsertSiteOverrideAction::class)->handle($live, 'category', self::MONITORS, 'local_slug', 'de-DE', 'bildschirme', 'German storefront wording');
            foreach (['en-US', 'de-DE'] as $code) {
                app(SiteSyncService::class)->syncCategory($live, $monitors->refresh(), $code);
            }

            Carbon::setTestNow('2026-10-09T09:00:00Z');
            app(ArchiveCentralCategoryAction::class)->handle($actor, $legacy->refresh());

            return $monitors->refresh();
        } finally {
            Carbon::setTestNow($previous);
        }
    }
}
