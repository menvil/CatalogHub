<?php

namespace Tests\Feature\SchemaConsumers;

use App\Actions\CategorySchema\MarkCategorySchemaReviewedAction;
use App\Actions\CategorySchema\SaveCategoryComparisonAction;
use App\Actions\CategorySchema\UnassignAttributeFromCategoryAction;
use App\Actions\CategorySchema\UpdateCategoryAttributeAssignmentAction;
use App\Actions\CategorySchema\UpdateGlobalAttributeDefinitionAction;
use App\Actions\Facets\SaveCategoryFacetAction;
use App\Actions\Imports\PublishNormalizedProductDraftToCentralAction;
use App\Actions\Imports\RejectNormalizedProductDraftAction;
use App\Actions\ProductAttributes\SaveProductSpecsAction;
use App\Domains\Projections\SiteSyncService;
use App\Exceptions\ProductAttributes\CannotSaveProductSpecsException;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use App\Models\User;
use App\Services\AttributeGlobalization\AttributeReconciliationWriter;
use App\Services\AttributeGlobalization\FinalizeSchemaConsumersV2;
use App\Services\AttributeGlobalization\LegacyAttributeBackfill;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
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

    #[DataProvider('cutoverWriters')]
    public function test_cutover_serializes_with_specs_publish_and_schema_and_writers_reload_identity(string $writer): void
    {
        $migration = require database_path('migrations/2026_10_06_000002_cut_over_schema_consumers.php');
        $migration->down();
        $actor = User::factory()->centralAdmin()->create();
        $a = CentralCategory::factory()->create();
        $b = CentralCategory::factory()->create();
        $legacy = DB::table('attribute_definitions')->insertGetId(['central_category_id' => $a->id, 'code' => 'legacy_meaning', 'name' => 'Meaning', 'data_type' => 'string']);
        $canonical = DB::table('attribute_definitions')->insertGetId(['central_category_id' => $b->id, 'code' => 'canonical_meaning', 'name' => 'Meaning', 'data_type' => 'string']);
        app(LegacyAttributeBackfill::class)->run();
        app(AttributeReconciliationWriter::class)->run(['version' => 1,
            'expected_write_epoch' => (int) DB::table('attribute_identity_scopes')->value('write_epoch'),
            'definitions' => [['legacy_definition_id' => $legacy, 'canonical_definition_id' => $canonical, 'canonical_code' => 'canonical_meaning']]], true, $actor);
        $assignment = CategoryAttributeAssignment::query()->where('central_category_id', $a->id)->sole();
        $product = CentralProduct::factory()->for($a, 'category')->create();
        $draft = NormalizedProductDraft::factory()->create(['category_id' => $a->id, 'matched_central_product_id' => $product->id,
            'attribute_identity_version' => 1, 'status' => $writer === 'reject' ? 'pending_review' : 'approved', 'attributes_json' => [['attribute_definition_id' => $legacy, 'code' => 'legacy_meaning', 'value_type' => 'string', 'value' => 'Reviewed']]]);
        $child = match ($writer) {
            'specs' => fn () => app(SaveProductSpecsAction::class)->handle($product, [$legacy => ['value_text' => 'Stale pointer']], $actor),
            'publish' => fn () => app(PublishNormalizedProductDraftToCentralAction::class)->handle($draft, $actor),
            'reject' => fn () => app(RejectNormalizedProductDraftAction::class)->handle($draft, $actor, 'Reviewed rejection'),
            'schema' => fn () => app(UpdateCategoryAttributeAssignmentAction::class)->handle($assignment, ['is_visible' => false], 2, $actor),
            default => throw new \InvalidArgumentException('Unknown writer.'),
        };
        $this->race('attribute_identity_scopes', fn () => $migration->up(), $child,
            function (string $outcome) use ($writer, $assignment, $canonical, $product, $draft, $actor, $child): void {
                self::assertSame('validation-error', $outcome);
                self::assertSame(0, (int) DB::table('attribute_identity_scopes')->value('consumer_version'));
                self::assertSame(0, CentralProductAttributeValue::query()->count());
                self::assertTrue(CategoryAttributeAssignment::query()->findOrFail($assignment->id)->is_visible);
                self::assertNotSame('rejected', NormalizedProductDraft::query()->findOrFail($draft->id)->status);
                app(FinalizeSchemaConsumersV2::class)->run($actor);
                if ($writer !== 'specs') {
                    $child();
                } else {
                    try {
                        $child();
                        self::fail('Old definition pointer was accepted after finalization.');
                    } catch (CannotSaveProductSpecsException|\Illuminate\Validation\ValidationException) {
                        self::assertSame(0, CentralProductAttributeValue::query()->count());
                    }
                }
                self::assertSame(2, (int) DB::table('attribute_identity_scopes')->value('consumer_version'));
                self::assertSame($canonical, CategoryAttributeAssignment::query()->findOrFail($assignment->id)->attribute_definition_id);
                self::assertSame(2, NormalizedProductDraft::query()->findOrFail($draft->id)->attribute_identity_version);
                if ($writer === 'publish') {
                    self::assertSame($canonical, CentralProductAttributeValue::query()->where('central_product_id', $product->id)->sole()->attribute_definition_id);
                } elseif ($writer === 'reject') {
                    self::assertSame('rejected', NormalizedProductDraft::query()->findOrFail($draft->id)->status);
                    self::assertSame(0, CentralProductAttributeValue::query()->count());
                } elseif ($writer === 'specs') {
                    self::assertSame(0, CentralProductAttributeValue::query()->count());
                } else {
                    self::assertFalse(CategoryAttributeAssignment::query()->findOrFail($assignment->id)->is_visible);
                }
            });
    }

    public static function cutoverWriters(): array
    {
        return [['specs'], ['publish'], ['reject'], ['schema']];
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
                self::assertSame(2, $projection->attribute_identity_version);
                self::assertSame(2, $search->attribute_identity_version);
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
