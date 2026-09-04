<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\SyncCentralBrandTagsAction;
use App\Enums\AuditAction;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CatalogTag;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

final class SyncCentralBrandTagsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_reuse_and_request_duplicates_share_one_global_identity(): void
    {
        $actor = User::factory()->create();
        $firstBrand = CentralBrand::factory()->create();
        $secondBrand = CentralBrand::factory()->create();
        $action = app(SyncCentralBrandTagsAction::class);

        $result = $action->handle($actor, $firstBrand, ['Premium', 'Gaming']);
        self::assertSame(['Gaming', 'Premium'], $result->tags->pluck('name')->all());
        self::assertDatabaseCount('catalog_tags', 2);
        self::assertDatabaseCount('central_brand_tag', 2);
        self::assertSame(1, $this->tagAuditsFor($firstBrand)->count());

        $premiumId = CatalogTag::query()->where('name', 'Premium')->value('id');
        $action->handle($actor, $secondBrand, ['premium', ' PREMIUM ', 'Premium']);

        self::assertDatabaseCount('catalog_tags', 2);
        self::assertSame([$premiumId], $secondBrand->fresh()->tags()->pluck('catalog_tags.id')->all());
        self::assertSame('Premium', CatalogTag::query()->findOrFail($premiumId)->name);
    }

    public function test_remove_and_clear_retain_unused_vocabulary_and_write_one_semantic_event_each(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $action = app(SyncCentralBrandTagsAction::class);
        $action->handle($actor, $brand, ['Premium', 'Gaming']);

        $action->handle($actor, $brand, ['Premium']);
        $remove = $this->tagAuditsFor($brand)->orderBy('id')->get()[1];
        self::assertSame(AuditAction::CatalogBrandTagsUpdated->value, $remove->action);
        self::assertSame(['tags' => ['Gaming', 'Premium']], $remove->before_json);
        self::assertSame(['tags' => ['Premium']], $remove->after_json);
        self::assertDatabaseCount('catalog_tags', 2);

        $action->handle($actor, $brand, []);
        $clear = $this->tagAuditsFor($brand)->latest('id')->firstOrFail();
        self::assertSame(['tags' => ['Premium']], $clear->before_json);
        self::assertSame(['tags' => []], $clear->after_json);
        self::assertDatabaseCount('central_brand_tag', 0);
        self::assertDatabaseCount('catalog_tags', 2);
    }

    public function test_semantically_identical_unordered_set_is_a_no_op(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $action = app(SyncCentralBrandTagsAction::class);
        $action->handle($actor, $brand, ['Gaming', 'Premium']);

        $updated = $action->handle($actor, $brand, ['premium', 'GAMING']);

        self::assertSame(['Gaming', 'Premium'], $updated->tags->pluck('name')->all());
        self::assertSame(1, $this->tagAuditsFor($brand)->count());
        self::assertDatabaseCount('catalog_tags', 2);
    }

    public function test_update_records_one_brand_centric_audit_snapshot_with_sorted_names(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $action = app(SyncCentralBrandTagsAction::class);
        $action->handle($actor, $brand, ['Premium', 'Gaming']);

        $action->handle($actor, $brand, ['Gaming', 'Enterprise']);

        $audit = $this->tagAuditsFor($brand)->latest('id')->firstOrFail();
        self::assertSame(AuditAction::CatalogBrandTagsUpdated->value, $audit->action);
        self::assertSame($brand->getMorphClass(), $audit->subject_type);
        self::assertSame((string) $brand->getKey(), $audit->subject_id);
        self::assertSame($actor->getKey(), $audit->actor_id);
        self::assertSame(['tags' => ['Gaming', 'Premium']], $audit->before_json);
        self::assertSame(['tags' => ['Enterprise', 'Gaming']], $audit->after_json);
        self::assertSame(2, $this->tagAuditsFor($brand)->count());
    }

    public function test_maximum_and_invalid_names_reject_the_whole_mutation(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $action = app(SyncCentralBrandTagsAction::class);

        foreach ([
            array_map(static fn (int $index): string => "Tag {$index}", range(1, 21)),
            ['   '],
            ["Line\nBreak"],
            ["Invalid\xC3\x28"],
            [str_repeat('x', 81)],
        ] as $invalidTags) {
            try {
                $action->handle($actor, $brand, $invalidTags);
                self::fail('Expected tag validation failure.');
            } catch (ValidationException) {
                self::assertDatabaseCount('catalog_tags', 0);
                self::assertDatabaseCount('central_brand_tag', 0);
                self::assertSame(0, $this->tagAuditsFor($brand)->count());
            }
        }
    }

    public function test_legitimate_labels_and_case_fold_expansion_are_supported(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $expandingLabel = str_repeat("\u{0130}", 80);

        app(SyncCentralBrandTagsAction::class)->handle($actor, $brand, [
            'B2B',
            'AI',
            'C++',
            'Eco-friendly',
            'Home & Living',
            'Premium',
            $expandingLabel,
        ]);

        self::assertSame(7, $brand->fresh()->tags()->count());
        self::assertSame(80, mb_strlen(CatalogTag::query()->where('name', $expandingLabel)->sole()->name));
        self::assertGreaterThan(80, mb_strlen(CatalogTag::query()->where('name', $expandingLabel)->sole()->normalized_name));
    }

    public function test_audit_failure_rolls_back_assignments_and_new_vocabulary(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $premium = CatalogTag::factory()->create(['name' => 'Premium']);
        $brand->tags()->attach($premium);

        $audit = $this->createMock(AuditRecorder::class);
        $audit->method('record')->willThrowException(new RuntimeException('audit unavailable'));
        $this->app->instance(AuditRecorder::class, $audit);

        try {
            app(SyncCentralBrandTagsAction::class)->handle($actor, $brand, ['Enterprise']);
            self::fail('Expected audit failure.');
        } catch (RuntimeException $exception) {
            self::assertSame('audit unavailable', $exception->getMessage());
        }

        self::assertSame(['Premium'], $brand->fresh()->tags()->pluck('name')->all());
        self::assertSame(['Premium'], CatalogTag::query()->pluck('name')->all());
        self::assertSame(0, $this->tagAuditsFor($brand)->count());
    }

    /** @return Builder<AuditLogEntry> */
    private function tagAuditsFor(CentralBrand $brand): Builder
    {
        return AuditLogEntry::query()
            ->where('action', AuditAction::CatalogBrandTagsUpdated->value)
            ->where('subject_type', $brand->getMorphClass())
            ->where('subject_id', (string) $brand->getKey());
    }
}
