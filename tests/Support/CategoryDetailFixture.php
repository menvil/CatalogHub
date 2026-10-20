<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Actions\CategorySchema\ApproveCategorySchemaAction;
use App\Actions\CategorySchema\AssignAttributeToCategoryAction;
use App\Actions\CategorySchema\MarkCategorySchemaReviewedAction;
use App\Actions\CategorySchema\SaveCategoryComparisonAction;
use App\Actions\CentralCatalog\ActivateCentralCategoryAction;
use App\Actions\CentralCatalog\ArchiveCentralCategoryAction;
use App\Actions\CentralCatalog\CreateCentralCategoryAction;
use App\Actions\Translations\ApproveTranslationAction;
use App\Actions\Translations\MarkTranslationOutdatedAction;
use App\Actions\Translations\SaveCategoryTranslationAction;
use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\FacetDefinition;
use App\Models\Locale;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Carbon;

final class CategoryDetailFixture
{
    public const VERSION = 'category-detail-v1';

    public static function create(): CentralCategory
    {
        $previous = Carbon::getTestNow();
        Carbon::setTestNow('2026-10-01T09:00:00Z');
        try {
            $author = User::factory()->centralAdmin()->create(['name' => 'Ada Catalog', 'email' => 'ada-ca017@fixture.test']);
            $reviewer = User::factory()->centralAdmin()->create(['name' => 'Sam Schema', 'email' => 'sam-ca017@fixture.test']);

            $root = CentralCategory::factory()->create(['id' => 195001, 'name' => 'Electronics', 'slug' => 'detail-electronics', 'status' => 'active', 'schema_status' => 'draft', 'parent_id' => null]);
            $parent = CentralCategory::factory()->create(['id' => 195002, 'name' => 'Displays', 'slug' => 'detail-displays', 'status' => 'active', 'schema_status' => 'draft', 'parent_id' => $root->id, 'position' => 0]);
            // A real create action provides trustworthy creation attribution. SQLite's
            // browser fixture yields ID 195003; tests use the returned stable ID.
            $category = app(CreateCentralCategoryAction::class)->handle($author, ['name' => 'Gaming Displays', 'slug' => 'detail-gaming-monitors', 'parent_id' => $parent->id], 0);
            CentralCategory::factory()->create(['id' => 195004, 'name' => 'Empty Category', 'slug' => 'detail-empty', 'status' => 'draft', 'schema_status' => 'draft', 'parent_id' => null]);
            $archived = CentralCategory::factory()->create(['id' => 195005, 'name' => 'Archived Monitors', 'slug' => 'detail-archived', 'status' => 'draft', 'schema_status' => 'draft', 'parent_id' => null]);
            $child = CentralCategory::factory()->create(['id' => 195006, 'name' => 'Esports Monitors', 'slug' => 'detail-esports', 'status' => 'draft', 'schema_status' => 'draft', 'parent_id' => $category->id]);
            Carbon::setTestNow('2026-10-02T10:00:00Z');
            // Historical identity change with an explicitly unknown actor; the nullable
            // recorder contract represents absence without inventing attribution.
            $category->update(['name' => 'Gaming Monitors']);
            app(AuditRecorder::class)->record(AuditAction::CatalogCategoryUpdated, AuditContext::Central, null, $category, null, ['name' => 'Gaming Displays'], ['name' => 'Gaming Monitors', 'changed_fields' => ['name']]);
            $sections = [];
            foreach (['display' => 'Display', 'connectivity' => 'Connectivity'] as $code => $name) {
                $sections[] = AttributeSection::factory()->create(['central_category_id' => $category->id, 'code' => $code, 'name' => $name, 'position' => count($sections)]);
            }
            $assignments = [];
            foreach (['Refresh rate' => 'integer', 'Panel type' => 'string', 'Connection' => 'string', 'HDR support' => 'boolean'] as $name => $type) {
                $i = count($assignments);
                $definition = AttributeDefinition::factory()->create(['code' => 'ca017_'.str($name)->snake(), 'name' => $name, 'data_type' => $type, 'measurement_dimension_id' => null, 'canonical_measurement_unit_id' => null]);
                $assignments[] = app(AssignAttributeToCategoryAction::class)->handle($category, $definition, ['attribute_section_id' => $sections[intdiv($i, 2)]->id, 'position' => $i % 2, 'is_required' => $i < 2, 'is_visible' => true, 'is_searchable' => false, 'is_sortable' => false], $category->refresh()->schema_revision, $reviewer);
                if ($i === 0) {
                    CategoryAttributeAssignment::factory()->create(['central_category_id' => $parent->id, 'attribute_definition_id' => $definition->id]);
                }
            }
            FacetDefinition::factory()->create(['category_id' => $category->id, 'code' => 'brand', 'source_type' => 'brand', 'is_active' => false, 'is_visible' => false]);
            app(SaveCategoryComparisonAction::class)->handle($category, [
                ['category_attribute_assignment_id' => $assignments[0]->id, 'position' => 0, 'is_visible' => true],
                ['category_attribute_assignment_id' => $assignments[1]->id, 'position' => 1, 'is_visible' => false],
            ], $category->refresh()->schema_revision, $reviewer);
            Carbon::setTestNow('2026-10-03T11:00:00Z');
            app(MarkCategorySchemaReviewedAction::class)->handle($category, $category->refresh()->schema_revision, $reviewer);
            Carbon::setTestNow('2026-10-04T12:00:00Z');
            app(ApproveCategorySchemaAction::class)->handle($category, $category->refresh()->schema_revision, $reviewer);
            Carbon::setTestNow('2026-10-05T13:00:00Z');
            app(ActivateCentralCategoryAction::class)->handle($author, $category);
            CentralProduct::factory()->create(['central_category_id' => $archived->id, 'name' => 'Retained archived-category monitor', 'slug' => 'ca017-retained-monitor', 'status' => 'active']);
            CategoryAttributeAssignment::factory()->create(['central_category_id' => $archived->id, 'attribute_definition_id' => $assignments[0]->attribute_definition_id]);
            app(ArchiveCentralCategoryAction::class)->handle($author, $archived);
            foreach (['draft', 'active', 'archived', 'active'] as $i => $status) {
                CentralProduct::factory()->create(['central_category_id' => $category->id, 'name' => 'Gaming Monitor '.($i + 1), 'slug' => 'ca017-monitor-'.$i, 'status' => $status]);
            }
            CentralProduct::factory()->count(3)->create(['central_category_id' => $parent->id]);
            CentralProduct::factory()->count(2)->create(['central_category_id' => $child->id]);
            foreach (['active', 'draft', 'suspended', 'archived', 'active'] as $i => $status) {
                $site = Site::factory()->create(['name' => ['Central Store', 'Preview Store', 'Suspended Store', 'Archived Store', 'Deleted Store'][$i], 'code' => 'ca017-site-'.$i, 'domain' => 'ca017-site-'.$i.'.test', 'status' => $status]);
                SiteCategory::query()->create(['site_id' => $site->id, 'central_category_id' => $category->id, 'is_enabled' => $i !== 1, 'local_status' => $i === 1 ? 'hidden' : null]);
                if ($i === 4) {
                    $site->delete();
                }
            }
            Locale::query()->update(['is_active' => false]);
            foreach (['en-US' => 'en', 'de-DE' => 'de', 'fr-FR' => 'fr', 'es-ES' => 'es', 'ja-JP' => 'ja'] as $code => $language) {
                $locale = Locale::query()->firstOrNew(['code' => $code]);
                $locale->forceFill(['language_code' => $language, 'region_code' => substr($code, 3), 'name' => $code, 'native_name' => $code, 'direction' => 'ltr', 'is_active' => true, 'is_default' => $code === 'en-US', 'position' => 0])->saveOrFail();
                if ($code === 'de-DE') {
                    continue;
                } // Explicit fallback scenario, no exact row.
                $translation = app(SaveCategoryTranslationAction::class)->handle($category->refresh(), $locale, ['name' => 'Gaming Monitors '.$code, 'description' => $code === 'en-US' ? 'High performance gaming monitors with fast refresh rates, adaptive sync and immersive displays.' : 'Localized monitor description '.$code, 'status' => $code === 'es-ES' ? TranslationStatus::MachineTranslated : TranslationStatus::HumanReviewed]);
                if ($code === 'en-US') {
                    app(ApproveTranslationAction::class)->handle($translation, $reviewer);
                }
                if ($code === 'ja-JP') {
                    app(MarkTranslationOutdatedAction::class)->handle($translation);
                }
            }

            return $category->refresh();
        } finally {
            Carbon::setTestNow($previous);
        }
    }
}
