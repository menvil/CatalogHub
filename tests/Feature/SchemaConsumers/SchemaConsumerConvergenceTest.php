<?php

namespace Tests\Feature\SchemaConsumers;

use App\Actions\CategorySchema\CloneCategorySchemaAction;
use App\Actions\CategorySchema\ExportCategorySchemaAction;
use App\Actions\CategorySchema\SaveCategoryComparisonAction;
use App\Actions\Facets\SaveCategoryFacetAction;
use App\Actions\Imports\SaveAttributeMappingAction;
use App\Actions\ProductAttributes\SaveProductSpecsAction;
use App\Domains\Projections\Builders\ProductProjectionBuilder;
use App\Domains\Projections\Builders\SearchDocumentBuilder;
use App\Domains\Projections\SiteSyncService;
use App\Domains\PublicSite\ComparisonViewModelBuilder;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\FacetDefinition;
use App\Models\Imports\AttributeMapping;
use App\Models\Imports\ImportSource;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Locale;
use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteFacetOverride;
use App\Models\SiteProduct;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use App\Models\Translations\AttributeSectionTranslation;
use App\Models\Translations\AttributeTranslation;
use App\Models\User;
use App\Services\Imports\AttributeNormalizer;
use App\Services\Imports\DraftAttributeIdentity;
use App\Services\ProductAttributes\MissingRequiredAttributesResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class SchemaConsumerConvergenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_target_schema_has_no_retired_authority(): void
    {
        foreach (['central_category_id', 'attribute_section_id', 'canonical_code', 'dimension', 'canonical_unit', 'position', 'is_required', 'is_visible', 'is_searchable', 'is_sortable', 'is_filterable', 'is_comparable'] as $field) {
            self::assertFalse(Schema::hasColumn('attribute_definitions', $field), $field);
        }
        self::assertFalse(Schema::hasColumn('attribute_mappings', 'attribute_definition_id'));
        self::assertFalse(Schema::hasColumn('facet_definitions', 'attribute_definition_id'));
    }

    public function test_shared_canonical_meaning_respects_local_required_visibility_search_sort_and_facet_code(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $a = CentralCategory::factory()->create(['name' => 'TVs']);
        $b = CentralCategory::factory()->create(['name' => 'Monitors']);
        $dimension = MeasurementDimension::factory()->create(['code' => 'length']);
        $unit = MeasurementUnit::factory()->create(['dimension_id' => $dimension->id, 'code' => 'inch', 'is_canonical' => true]);
        $definition = AttributeDefinition::factory()->create(['code' => 'screen_size', 'data_type' => 'decimal', 'measurement_dimension_id' => $dimension->id, 'canonical_measurement_unit_id' => $unit->id]);
        $section = AttributeSection::factory()->create(['central_category_id' => $a->id]);
        $one = CategoryAttributeAssignment::factory()->create(['central_category_id' => $a->id, 'attribute_definition_id' => $definition->id, 'attribute_section_id' => $section->id, 'position' => 3, 'is_required' => true, 'is_visible' => false, 'is_searchable' => true, 'is_sortable' => false]);
        $two = CategoryAttributeAssignment::factory()->create(['central_category_id' => $b->id, 'attribute_definition_id' => $definition->id, 'position' => 1, 'is_required' => false, 'is_visible' => true, 'is_searchable' => false, 'is_sortable' => true]);
        $tv = CentralProduct::factory()->create(['central_category_id' => $a->id]);
        $monitor = CentralProduct::factory()->create(['central_category_id' => $b->id]);
        self::assertSame([$definition->id], array_map(fn ($d) => $d->id, app(MissingRequiredAttributesResolver::class)->resolve($tv)));
        self::assertSame([], app(MissingRequiredAttributesResolver::class)->resolve($monitor));
        app(SaveProductSpecsAction::class)->handle($tv, [$definition->id => ['value_number' => '55', 'source_unit' => 'inch']], $actor);
        app(SaveProductSpecsAction::class)->handle($monitor, [$definition->id => ['value_number' => '27', 'source_unit' => 'inch']], $actor);
        $fact = CentralProductAttributeValue::query()->where('central_product_id', $tv->id)->sole()->getRawOriginal();
        FacetDefinition::factory()->create(['category_id' => $a->id, 'category_attribute_assignment_id' => $one->id, 'source_type' => 'attribute', 'facet_type' => 'range', 'code' => 'diagonal_filter']);
        $site = Site::factory()->create();
        $first = app(ProductProjectionBuilder::class)->build($site, $tv, 'en-US');
        $second = app(ProductProjectionBuilder::class)->build($site, $monitor, 'en-US');
        self::assertSame([], $first->payload['spec_sections']);
        self::assertSame('ungrouped', $second->payload['spec_sections'][0]['code']);
        self::assertSame($one->id, $first->payload['attributes'][0]['assignment_id']);
        self::assertSame($two->id, $second->payload['attributes'][0]['assignment_id']);
        $searchA = app(SearchDocumentBuilder::class)->fromProductProjection($first);
        $searchB = app(SearchDocumentBuilder::class)->fromProductProjection($second);
        self::assertEquals(55, $searchA->filterValues['diagonal_filter']);
        self::assertArrayNotHasKey('screen_size', $searchA->filterValues);
        self::assertArrayNotHasKey('screen_size', $searchA->sortValues);
        self::assertEquals(27, $searchB->sortValues['screen_size']);
        self::assertStringContainsString('55', $searchA->searchText);
        self::assertSame($fact, CentralProductAttributeValue::query()->where('central_product_id', $tv->id)->sole()->getRawOriginal());
    }

    public function test_enum_draft_rejects_conflicting_metadata_option_identity(): void
    {
        $assignment = CategoryAttributeAssignment::factory()->create(['attribute_definition_id' => AttributeDefinition::factory()->create(['data_type' => 'enum'])->id]);
        $one = $assignment->definition->options()->create(['code' => 'first', 'label' => 'First']);
        $two = $assignment->definition->options()->create(['code' => 'second', 'label' => 'Second']);
        $draft = NormalizedProductDraft::factory()->create(['category_id' => $assignment->central_category_id, 'schema_version' => 1,
            'attributes_json' => [['category_attribute_assignment_id' => $assignment->id, 'attribute_definition_id' => $assignment->attribute_definition_id,
                'code' => $assignment->definition->code, 'value_type' => 'enum', 'value_enum_code' => $one->code, 'metadata' => ['option_id' => $two->id]]]]);
        $this->expectException(ValidationException::class);
        app(DraftAttributeIdentity::class)->candidates($draft);
    }

    public function test_clone_and_export_keep_global_ids_and_options_shared(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create(['attribute_definition_id' => AttributeDefinition::factory()->create(['data_type' => 'enum'])->id]);
        $assignment->definition->options()->create(['code' => 'historical', 'label' => 'Historical', 'position' => 0, 'is_visible' => false]);
        $target = CentralCategory::factory()->create();
        app(CloneCategorySchemaAction::class)->handle($assignment->category, $target, $actor);
        self::assertSame($assignment->attribute_definition_id, $target->attributeAssignments()->sole()->attribute_definition_id);
        self::assertSame(1, AttributeDefinition::query()->count());
        $export = app(ExportCategorySchemaAction::class)->handle($target, $actor);
        self::assertSame(1, $export['schema_version']);
        self::assertSame($assignment->attribute_definition_id, $export['definitions'][0]['id']);
        self::assertFalse($export['definitions'][0]['options'][0]['is_visible']);
        self::assertArrayNotHasKey('is_required', $export['definitions'][0]);
    }

    public function test_cross_consumer_acceptance_and_idempotent_site_locale_rebuild(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $locale = Locale::factory()->create(['code' => 'en-US']);
        $site = Site::factory()->create(['default_locale' => 'en-US']);
        $tvCategory = CentralCategory::factory()->create(['name' => 'TVs', 'status' => 'active']);
        $monitorCategory = CentralCategory::factory()->create(['name' => 'Monitors', 'status' => 'active']);
        $dimension = MeasurementDimension::factory()->create(['code' => 'length']);
        $unit = MeasurementUnit::factory()->create(['dimension_id' => $dimension->id, 'code' => 'inch', 'is_canonical' => true]);
        $screen = AttributeDefinition::factory()->create(['code' => 'screen_size', 'name' => 'Screen size', 'data_type' => 'decimal', 'measurement_dimension_id' => $dimension->id, 'canonical_measurement_unit_id' => $unit->id]);
        $panel = AttributeDefinition::factory()->create(['code' => 'panel_type', 'name' => 'Panel type', 'data_type' => 'enum']);
        $hiddenOption = $panel->options()->create(['code' => 'ips', 'label' => 'IPS historical', 'position' => 0, 'is_visible' => false]);
        $source = ImportSource::factory()->create();
        $products = [];
        $assignments = [];
        foreach ([$tvCategory, $monitorCategory] as $offset => $category) {
            $section = AttributeSection::factory()->create(['central_category_id' => $category->id, 'name' => $offset === 0 ? 'TV display' : 'Monitor panel']);
            $assignment = CategoryAttributeAssignment::factory()->create(['central_category_id' => $category->id, 'attribute_definition_id' => $screen->id,
                'attribute_section_id' => $section->id, 'position' => $offset + 2, 'is_required' => $offset === 0, 'is_visible' => $offset !== 0,
                'is_searchable' => $offset === 0, 'is_sortable' => $offset !== 0]);
            $assignments[] = $assignment;
            $enumAssignment = CategoryAttributeAssignment::factory()->create(['central_category_id' => $category->id, 'attribute_definition_id' => $panel->id, 'attribute_section_id' => $section->id]);
            SiteCategory::query()->create(['site_id' => $site->id, 'central_category_id' => $category->id, 'is_enabled' => true]);
            app(SaveAttributeMappingAction::class)->handle(null, ['import_source_id' => $source->id, 'category_id' => $category->id,
                'raw_key' => 'Diagonal', 'category_attribute_assignment_id' => $assignment->id, 'status' => 'reviewed', 'confidence' => 1, 'mapping_type' => 'attribute'], $actor);
            $mapping = AttributeMapping::query()->where('category_id', $category->id)->sole();
            self::assertSame($screen->id, $mapping->assignment->attribute_definition_id);
            AttributeSectionTranslation::factory()->create(['attribute_section_id' => $section->id, 'locale_id' => $locale->id, 'locale' => $locale->code, 'name' => $offset === 0 ? 'TV translated display' : 'Monitor translated panel', 'status' => 'approved']);
            foreach (range(0, $offset === 0 ? 1 : 0) as $number) {
                $product = CentralProduct::factory()->create(['central_category_id' => $category->id, 'status' => 'active']);
                app(SaveProductSpecsAction::class)->handle($product, [$screen->id => ['value_number' => $offset === 0 ? '55' : '27', 'source_unit' => 'inch', 'raw_value' => 'original diagonal']], $actor);
                CentralProductAttributeValue::factory()->forAssignment($enumAssignment)->create(['central_product_id' => $product->id, 'value_text' => null, 'value_type' => 'enum', 'value_enum_code' => $hiddenOption->code, 'raw_value' => 'original IPS']);
                SiteProduct::factory()->create(['site_id' => $site->id, 'central_product_id' => $product->id]);
                $products[] = $product;
            }
            app(SaveCategoryComparisonAction::class)->handle($category, [
                ['category_attribute_assignment_id' => $assignment->id, 'position' => 0, 'is_visible' => true],
                ['category_attribute_assignment_id' => $enumAssignment->id, 'position' => 1, 'is_visible' => true]], $category->fresh()->schema_revision, $actor);
        }
        AttributeTranslation::factory()->create(['attribute_definition_id' => $screen->id, 'locale_id' => $locale->id, 'locale' => $locale->code, 'label' => 'Screen diagonal', 'status' => 'approved']);
        $facet = app(SaveCategoryFacetAction::class)->handle($tvCategory, null, ['category_attribute_assignment_id' => $assignments[0]->id,
            'source_type' => 'attribute', 'facet_type' => 'range', 'code' => 'diagonal_filter'], $tvCategory->fresh()->schema_revision, $actor);
        $override = SiteFacetOverride::factory()->create(['site_id' => $site->id, 'facet_definition_id' => $facet->id, 'label_override' => 'Site diagonal']);
        $candidate = app(AttributeNormalizer::class)->candidate($assignments[1], '27 inch');
        $draft = NormalizedProductDraft::factory()->create(['category_id' => $monitorCategory->id, 'schema_version' => 1, 'attributes_json' => [$candidate]]);
        self::assertSame($assignments[1]->id, app(DraftAttributeIdentity::class)->candidates($draft)[0]['category_attribute_assignment_id']);
        $facts = CentralProductAttributeValue::query()->orderBy('id')->get()->toJson();
        $sync = app(SiteSyncService::class);
        $first = $sync->syncSite($site);
        self::assertSame([], $first['failures']);
        self::assertSame(2, $first['categories']);
        self::assertSame(3, $first['products']);
        $checksums = SiteSearchDocument::query()->orderBy('id')->pluck('checksum')->all();
        self::assertCount(5, $checksums);
        self::assertSame(0, SiteSearchDocument::query()->where('schema_version', '!=', 1)->count());
        $second = $sync->syncSite($site);
        self::assertSame($first, $second);
        self::assertSame($checksums, SiteSearchDocument::query()->orderBy('id')->pluck('checksum')->all());
        self::assertSame($facts, CentralProductAttributeValue::query()->orderBy('id')->get()->toJson());
        $projections = SiteProductProjection::query()->whereIn('central_product_id', [$products[0]->id, $products[1]->id])->get();
        $comparison = app(ComparisonViewModelBuilder::class)->build($projections);
        self::assertNull($comparison['error']);
        self::assertTrue($comparison['sections'][0]['attributes'][0]['is_equal']);
        self::assertSame(['IPS historical', 'IPS historical'], $comparison['sections'][0]['attributes'][1]['values']);
        self::assertSame($override->id, SiteFacetOverride::query()->sole()->id);
    }
}
