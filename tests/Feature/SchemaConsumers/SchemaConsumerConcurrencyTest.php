<?php

namespace Tests\Feature\SchemaConsumers;

use App\Actions\CategorySchema\MarkCategorySchemaReviewedAction;
use App\Actions\CategorySchema\SaveCategoryComparisonAction;
use App\Actions\CategorySchema\UnassignAttributeFromCategoryAction;
use App\Actions\CategorySchema\UpdateGlobalAttributeDefinitionAction;
use App\Actions\Facets\SaveCategoryFacetAction;
use App\Actions\Imports\PublishNormalizedProductDraftToCentralAction;
use App\Actions\Imports\SaveAttributeMappingAction;
use App\Actions\ProductAttributes\SaveProductSpecsAction;
use App\Domains\Projections\SiteSyncService;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\Imports\AttributeMapping;
use App\Models\Imports\ImportSource;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\AttributeIdentityRace;
use Tests\TestCase;

final class SchemaConsumerConcurrencyTest extends TestCase
{
    use AttributeIdentityRace;
    use DatabaseTruncation;

    protected function beforeTruncatingDatabase(): void
    {
        RefreshDatabaseState::$migrated = false;
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_reviewed_mapping_creation_and_unassignment_cannot_commit_incompatible_membership(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        $source = ImportSource::factory()->create();
        $this->race('attribute_identity_scopes',
            fn () => app(UnassignAttributeFromCategoryAction::class)->handle($assignment, 1, $actor),
            fn () => app(SaveAttributeMappingAction::class)->handle(null, ['import_source_id' => $source->id,
                'category_id' => $assignment->central_category_id, 'raw_key' => 'Spec', 'mapping_type' => 'attribute',
                'status' => 'reviewed', 'confidence' => 1, 'category_attribute_assignment_id' => $assignment->id], $actor),
            function (string $outcome): void {
                self::assertSame('validation-error', $outcome);
                self::assertSame(0, AttributeMapping::query()->count());
                self::assertSame(0, CategoryAttributeAssignment::query()->count());
            });
    }

    public function test_import_publish_and_unassignment_serialize_and_preserve_published_fact(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        $draft = NormalizedProductDraft::factory()->create(['category_id' => $assignment->central_category_id,
            'status' => 'approved', 'attributes_json' => [['category_attribute_assignment_id' => $assignment->id,
                'attribute_definition_id' => $assignment->attribute_definition_id, 'code' => $assignment->definition->code,
                'value_type' => 'string', 'value' => 'Published fact']]]);
        $this->race('attribute_identity_scopes',
            fn () => app(PublishNormalizedProductDraftToCentralAction::class)->handle($draft, $actor),
            fn () => app(UnassignAttributeFromCategoryAction::class)->handle($assignment, 1, $actor),
            function (string $outcome): void {
                self::assertSame('validation-error', $outcome);
                self::assertSame(1, CategoryAttributeAssignment::query()->count());
                self::assertSame('Published fact', CentralProductAttributeValue::query()->sole()->value_text);
                self::assertSame('published', NormalizedProductDraft::query()->sole()->status);
            });
    }

    public function test_assignment_removal_and_specs_write_cannot_create_an_orphan(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        $product = CentralProduct::factory()->for($assignment->category, 'category')->create();
        $this->race('attribute_identity_scopes', fn () => app(UnassignAttributeFromCategoryAction::class)->handle($assignment, 1, $actor),
            fn () => app(SaveProductSpecsAction::class)->handle($product, [$assignment->attribute_definition_id => ['value_text' => 'Orphan']], $actor),
            function (string $outcome): void {
                self::assertSame('validation-error', $outcome);
                self::assertSame(0, CentralProductAttributeValue::query()->count());
                self::assertSame(0, CategoryAttributeAssignment::query()->count());
            });
    }

    public function test_global_edit_and_projection_search_rebuild_serialize_and_reload_category_revision(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create(['is_searchable' => true]);
        $product = CentralProduct::factory()->for($assignment->category, 'category')->create(['status' => 'active']);
        CentralProductAttributeValue::factory()->forAssignment($assignment)->create(['central_product_id' => $product->id, 'value_text' => 'Historical fact']);
        $site = Site::factory()->create();
        SiteProduct::factory()->create(['site_id' => $site->id, 'central_product_id' => $product->id]);
        $this->race('attribute_identity_scopes',
            fn () => app(UpdateGlobalAttributeDefinitionAction::class)->handle($assignment->definition, ['name' => 'Reviewed global name'], $actor),
            fn () => app(SiteSyncService::class)->syncProduct($site, $product, 'en-US'),
            function (string $outcome) use ($assignment): void {
                self::assertSame('success', $outcome);
                $category = CentralCategory::query()->findOrFail($assignment->central_category_id);
                self::assertSame(2, $category->schema_revision);
                $projection = SiteProductProjection::query()->sole();
                $search = SiteSearchDocument::query()->sole();
                self::assertSame($category->schema_revision, $projection->schema_revision);
                self::assertSame($category->schema_revision, $search->schema_revision);
                self::assertSame(1, $projection->schema_version);
                self::assertSame(1, $search->schema_version);
                self::assertSame('Reviewed global name', $projection->payload_json['attributes'][0]['label']);
            }, 'projection_jobs');
    }

    #[DataProvider('configurationOwners')]
    public function test_facet_and_comparison_changes_serialize_with_review_and_reject_stale_review(string $owner): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        $category = $assignment->category;
        $change = $owner === 'facet'
            ? fn () => app(SaveCategoryFacetAction::class)->handle($category, null, ['code' => 'maker', 'source_type' => 'brand', 'facet_type' => 'checkbox'], 1, $actor)
            : fn () => app(SaveCategoryComparisonAction::class)->handle($category, [['category_attribute_assignment_id' => $assignment->id, 'position' => 0, 'is_visible' => true]], 1, $actor);
        $this->race('attribute_identity_scopes', $change,
            fn () => app(MarkCategorySchemaReviewedAction::class)->handle($category, 1, $actor),
            function (string $outcome) use ($category): void {
                self::assertSame('stale', $outcome);
                self::assertSame(2, CentralCategory::query()->findOrFail($category->id)->schema_revision);
                self::assertNull(CentralCategory::query()->findOrFail($category->id)->schema_reviewed_revision);
            });
    }

    public static function configurationOwners(): array
    {
        return [['facet'], ['comparison']];
    }
}
