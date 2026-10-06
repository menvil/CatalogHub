<?php

namespace Tests\Feature\SchemaConsumers;

use App\Domains\Projections\SiteSyncService;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Locale;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProduct;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use App\Models\User;
use App\Queries\Attributes\SchemaConsumerPreflightV2Query;
use App\Queries\PublicSite\PublicProductSearchQuery;
use App\Services\AttributeGlobalization\AttributeIdentityLock;
use App\Services\AttributeGlobalization\DraftAttributeIdentityV2;
use App\Services\AttributeGlobalization\FinalizeSchemaConsumersV2;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Support\AttributeIdentityRace;
use Tests\TestCase;

final class SchemaConsumerFinalizationTest extends TestCase
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

    private function migration(): object
    {
        return require database_path('migrations/2026_10_06_000002_cut_over_schema_consumers.php');
    }

    private function legacyFixture(): array
    {
        $this->migration()->down();
        $actor = User::factory()->centralAdmin()->create();
        $locale = Locale::factory()->create(['code' => 'en-US']);
        $site = Site::factory()->create(['default_locale' => $locale->code]);
        $category = CentralCategory::factory()->create(['status' => 'active']);
        $product = CentralProduct::factory()->create(['central_category_id' => $category->id, 'status' => 'active']);
        SiteCategory::query()->create(['site_id' => $site->id, 'central_category_id' => $category->id, 'is_enabled' => true]);
        SiteProduct::factory()->create(['site_id' => $site->id, 'central_product_id' => $product->id]);
        $draft = NormalizedProductDraft::factory()->create(['category_id' => $category->id, 'attribute_identity_version' => 1, 'status' => 'approved', 'attributes_json' => []]);
        SiteProductProjection::query()->create(['site_id' => $site->id, 'locale' => $locale->code, 'central_product_id' => $product->id,
            'slug' => $product->slug, 'title' => 'Historical v1', 'status' => 'active', 'attribute_identity_version' => 1, 'payload_json' => ['historical' => true]]);
        SiteSearchDocument::query()->create(['site_id' => $site->id, 'locale' => $locale->code, 'document_type' => 'product', 'document_id' => $product->id,
            'title' => 'Historical v1', 'status' => 'active', 'attribute_identity_version' => 1]);

        return [$actor, $site, $product, $draft];
    }

    public function test_historical_migration_is_independent_of_live_projection_identity_and_preflight_services(): void
    {
        [, $site, , $draft] = $this->legacyFixture();
        foreach ([SiteSyncService::class, DraftAttributeIdentityV2::class, SchemaConsumerPreflightV2Query::class] as $service) {
            $this->app->bind($service, fn () => throw new \RuntimeException('Historical migration resolved a live runtime service: '.$service));
        }
        $this->migration()->up();
        self::assertSame(0, (int) DB::table('attribute_identity_scopes')->value('consumer_version'));
        self::assertTrue((bool) DB::table('attribute_identity_scopes')->value('cutover_ready_for_finalization'));
        self::assertSame(2, $draft->fresh()->attribute_identity_version);
        self::assertSame('stale', SiteProductProjection::query()->sole()->getRawOriginal('status'));
        self::assertSame(['historical' => true], SiteProductProjection::query()->sole()->payload_json);
        self::assertSame('stale', SiteSearchDocument::query()->sole()->getRawOriginal('status'));
        self::assertCount(0, app(PublicProductSearchQuery::class)->search($site, 'en-US', 'Historical'));
    }

    public function test_post_migration_finalize_rebuilds_and_verifies_v2_before_resuming_and_is_idempotent(): void
    {
        [$actor, $site, $product] = $this->legacyFixture();
        $this->migration()->up();
        try {
            DB::transaction(fn () => app(AttributeIdentityLock::class)->acquireTarget());
            self::fail('Writes resumed before rebuild.');
        } catch (ValidationException) {
            self::assertSame(0, (int) DB::table('attribute_identity_scopes')->value('consumer_version'));
        }
        $epoch = DB::table('attribute_identity_scopes')->value('cutover_write_epoch');
        $this->artisan('catalog:finalize-schema-consumer-v2', ['--actor' => $actor->id])->assertSuccessful();
        self::assertSame(2, (int) DB::table('attribute_identity_scopes')->value('consumer_version'));
        self::assertSame($epoch, DB::table('attribute_identity_scopes')->value('write_epoch'));
        self::assertSame(2, SiteProductProjection::query()->sole()->attribute_identity_version);
        self::assertSame(2, SiteCategoryProjection::query()->sole()->attribute_identity_version);
        self::assertSame('active', SiteProductProjection::query()->sole()->getRawOriginal('status'));
        self::assertSame($product->name, SiteProductProjection::query()->sole()->title);
        self::assertSame(2, SiteSearchDocument::query()->where('document_type', 'product')->sole()->attribute_identity_version);
        self::assertCount(1, app(PublicProductSearchQuery::class)->search($site, 'en-US', $product->name));
        $checksum = SiteProductProjection::query()->sole()->checksum;
        self::assertTrue(app(FinalizeSchemaConsumersV2::class)->run($actor)['already_finalized']);
        self::assertSame($checksum, SiteProductProjection::query()->sole()->checksum);
        $this->migration()->down();
        self::assertSame(1, (int) DB::table('attribute_identity_scopes')->value('consumer_version'));
    }

    public function test_failed_rebuild_rolls_back_and_keeps_public_output_stale_and_writes_paused(): void
    {
        [$actor, $site] = $this->legacyFixture();
        $this->migration()->up();
        $fail = true;
        DB::connection()->beforeExecuting(function (string $sql) use (&$fail): void {
            if ($fail && (str_starts_with(strtolower($sql), 'insert') || str_starts_with(strtolower($sql), 'update')) && str_contains($sql, 'site_product_projections')) {
                $fail = false;
                throw new \RuntimeException('Simulated Product projection persistence failure.');
            }
        });
        try {
            app(FinalizeSchemaConsumersV2::class)->run($actor);
            self::fail('Failed rebuild resumed writers.');
        } catch (\RuntimeException $error) {
            self::assertStringContainsString('writers remain paused', $error->getMessage());
        }
        self::assertSame(0, (int) DB::table('attribute_identity_scopes')->value('consumer_version'));
        self::assertFalse($fail);
        self::assertSame(0, SiteCategoryProjection::query()->count());
        self::assertSame('stale', SiteProductProjection::query()->sole()->getRawOriginal('status'));
        self::assertCount(0, app(PublicProductSearchQuery::class)->search($site, 'en-US', 'Historical'));
    }

    public function test_fresh_empty_install_activates_v2_without_manual_finalization(): void
    {
        $this->migration()->down();
        $this->migration()->up();
        self::assertSame(2, (int) DB::table('attribute_identity_scopes')->value('consumer_version'));
        self::assertTrue((bool) DB::table('attribute_identity_scopes')->value('cutover_ready_for_finalization'));
        DB::transaction(fn () => app(AttributeIdentityLock::class)->acquireTarget());
    }

    public function test_incomplete_contraction_cannot_be_finalized(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        DB::table('attribute_identity_scopes')->update(['consumer_version' => 0, 'cutover_ready_for_finalization' => false]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('contraction has not completed');
        app(FinalizeSchemaConsumersV2::class)->run($actor);
    }

    public function test_finalizer_fails_closed_for_an_unauthorized_actor(): void
    {
        $this->expectException(AuthorizationException::class);
        app(FinalizeSchemaConsumersV2::class)->run(User::factory()->create());
    }

    public function test_finalization_and_target_writer_serialize_until_verified_output_commits(): void
    {
        [$actor] = $this->legacyFixture();
        $this->migration()->up();
        $this->race('attribute_identity_scopes', fn () => app(FinalizeSchemaConsumersV2::class)->run($actor),
            fn () => DB::transaction(fn () => app(AttributeIdentityLock::class)->acquireTarget()),
            function (string $outcome): void {
                self::assertSame('success', $outcome);
                self::assertSame(2, (int) DB::table('attribute_identity_scopes')->value('consumer_version'));
                self::assertSame('active', SiteProductProjection::query()->sole()->getRawOriginal('status'));
                self::assertSame(2, SiteProductProjection::query()->sole()->attribute_identity_version);
            });
    }
}
