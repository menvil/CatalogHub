<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\Imports\LinkCentralBrandExternalIdentityAction;
use App\Actions\Imports\RemoveCentralBrandExternalIdentityAction;
use App\Actions\Imports\UpdateCentralBrandExternalIdentityAction;
use App\Enums\AuditAction;
use App\Exceptions\Imports\ExternalIdentityConflictException;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Imports\CentralBrandExternalIdentity;
use App\Models\Imports\ImportSource;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Imports\CentralBrandExternalIdentityResolver;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

final class CentralBrandExternalIdentityActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_link_preserves_opaque_id_reuses_source_and_records_safe_brand_audit(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $source = ImportSource::factory()->create([
            'code' => 'manufacturer_api',
            'config_json' => ['token' => 'super-secret-token'],
        ]);

        $identity = app(LinkCentralBrandExternalIdentityAction::class)->handle(
            $actor,
            $brand,
            $source,
            '  000123  ',
            ' https://example.test/brands/000123 ',
        );

        self::assertSame('000123', $identity->external_id);
        self::assertSame(hash('sha256', '000123'), $identity->external_id_hash);
        self::assertSame($brand->id, $identity->central_brand_id);
        self::assertTrue($brand->is($source->brandExternalIdentities()->sole()->brand));
        self::assertTrue($source->is($brand->externalIdentities()->sole()->source));

        $audit = AuditLogEntry::query()->sole();
        self::assertSame(AuditAction::CatalogBrandExternalIdentityLinked->value, $audit->action);
        self::assertSame($actor->id, $audit->actor_id);
        self::assertSame($brand->getMorphClass(), $audit->subject_type);
        self::assertSame((string) $brand->id, $audit->subject_id);
        self::assertNull($audit->before_json);
        self::assertSame([
            'source_code' => 'manufacturer_api',
            'external_id' => '000123',
            'external_url' => 'https://example.test/brands/000123',
        ], $audit->after_json);
        self::assertArrayNotHasKey('import_source_id', $audit->after_json);
        self::assertArrayNotHasKey('external_id_hash', $audit->after_json);
        self::assertStringNotContainsString('super-secret-token', json_encode($audit->after_json, JSON_THROW_ON_ERROR));
    }

    public function test_link_is_idempotent_for_same_brand_and_conflicts_for_another_brand(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $otherBrand = CentralBrand::factory()->create();
        $source = ImportSource::factory()->create();
        $action = app(LinkCentralBrandExternalIdentityAction::class);
        $first = $action->handle($actor, $brand, $source, 'ABC', 'https://example.test/original');

        $again = $action->handle($actor, $brand, $source, 'ABC', 'https://example.test/ignored');
        self::assertTrue($first->is($again));
        self::assertSame('https://example.test/original', $again->external_url);
        self::assertDatabaseCount('central_brand_external_identities', 1);
        self::assertDatabaseCount('audit_log_entries', 1);

        try {
            $action->handle($actor, $otherBrand, $source, 'ABC', null);
            self::fail('Expected an external identity conflict.');
        } catch (ExternalIdentityConflictException $exception) {
            self::assertStringContainsString('already linked', $exception->getMessage());
        }

        self::assertSame($brand->id, $first->fresh()->central_brand_id);
        self::assertDatabaseCount('audit_log_entries', 1);
    }

    public function test_ids_are_case_sensitive_per_source_and_independent_between_sources(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $sourceA = ImportSource::factory()->create();
        $sourceB = ImportSource::factory()->create();
        $action = app(LinkCentralBrandExternalIdentityAction::class);

        $action->handle($actor, $brand, $sourceA, 'ABC', null);
        $action->handle($actor, $brand, $sourceA, 'abc', null);
        $action->handle($actor, $brand, $sourceB, 'ABC', null);

        self::assertDatabaseCount('central_brand_external_identities', 3);
    }

    public function test_malformed_utf8_is_rejected_before_identity_persistence(): void
    {
        $this->expectException(ValidationException::class);

        try {
            app(LinkCentralBrandExternalIdentityAction::class)->handle(
                User::factory()->create(),
                CentralBrand::factory()->create(),
                ImportSource::factory()->create(),
                "external\xC3\x28",
                null,
            );
        } finally {
            self::assertDatabaseCount('central_brand_external_identities', 0);
            self::assertDatabaseCount('audit_log_entries', 0);
        }
    }

    public function test_inactive_source_rejects_new_link_but_existing_identity_can_be_updated_and_removed(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $source = ImportSource::factory()->create(['status' => 'inactive']);

        try {
            app(LinkCentralBrandExternalIdentityAction::class)->handle($actor, $brand, $source, '123', null);
            self::fail('Expected inactive source validation failure.');
        } catch (ValidationException) {
            self::assertDatabaseCount('central_brand_external_identities', 0);
        }

        $identity = CentralBrandExternalIdentity::factory()
            ->for($brand, 'brand')->for($source, 'source')->externalId('OLD')->create();
        app(UpdateCentralBrandExternalIdentityAction::class)->handle($actor, $brand, $identity, 'NEW', null);
        self::assertSame('NEW', $identity->fresh()->external_id);

        app(RemoveCentralBrandExternalIdentityAction::class)->handle($actor, $brand, $identity);
        self::assertDatabaseMissing('central_brand_external_identities', ['id' => $identity->id]);
        self::assertDatabaseHas('import_sources', ['id' => $source->id]);
    }

    public function test_update_changes_id_and_url_resolver_state_and_writes_minimal_audit(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $source = ImportSource::factory()->create(['code' => 'legacy_feed']);
        $identity = CentralBrandExternalIdentity::factory()
            ->for($brand, 'brand')->for($source, 'source')->externalId('OLD')->create(['external_url' => null]);

        $updated = app(UpdateCentralBrandExternalIdentityAction::class)->handle(
            $actor,
            $brand,
            $identity,
            'NEW',
            'https://example.test/new',
        );

        $resolver = app(CentralBrandExternalIdentityResolver::class);
        self::assertNull($resolver->findBrand($source, 'OLD'));
        self::assertTrue($brand->is($resolver->findBrand($source, 'NEW')));
        self::assertSame($identity->id, $updated->id);

        $audit = AuditLogEntry::query()->sole();
        self::assertSame(AuditAction::CatalogBrandExternalIdentityUpdated->value, $audit->action);
        self::assertSame(['source_code' => 'legacy_feed', 'external_id' => 'OLD', 'external_url' => null], $audit->before_json);
        self::assertSame(['source_code' => 'legacy_feed', 'external_id' => 'NEW', 'external_url' => 'https://example.test/new'], $audit->after_json);

        app(UpdateCentralBrandExternalIdentityAction::class)->handle($actor, $brand, $updated, 'NEW', 'https://example.test/new');
        self::assertDatabaseCount('audit_log_entries', 1);
    }

    public function test_update_conflict_and_cross_brand_ownership_leave_original_unchanged(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $otherBrand = CentralBrand::factory()->create();
        $source = ImportSource::factory()->create();
        $identity = CentralBrandExternalIdentity::factory()->for($brand, 'brand')->for($source, 'source')->externalId('OLD')->create();
        CentralBrandExternalIdentity::factory()->for($otherBrand, 'brand')->for($source, 'source')->externalId('TAKEN')->create();
        $action = app(UpdateCentralBrandExternalIdentityAction::class);

        try {
            $action->handle($actor, $brand, $identity, 'TAKEN', null);
            self::fail('Expected conflict.');
        } catch (ExternalIdentityConflictException) {
            self::assertSame('OLD', $identity->fresh()->external_id);
        }

        $this->expectException(ModelNotFoundException::class);
        $action->handle($actor, $otherBrand, $identity, 'NEW', null);
    }

    public function test_remove_writes_safe_unlink_audit(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $source = ImportSource::factory()->create(['code' => 'manufacturer_api']);
        $identity = CentralBrandExternalIdentity::factory()->for($brand, 'brand')->for($source, 'source')->externalId('123')->create();

        app(RemoveCentralBrandExternalIdentityAction::class)->handle($actor, $brand, $identity);

        $audit = AuditLogEntry::query()->sole();
        self::assertSame(AuditAction::CatalogBrandExternalIdentityUnlinked->value, $audit->action);
        self::assertSame('manufacturer_api', $audit->before_json['source_code']);
        self::assertSame('123', $audit->before_json['external_id']);
        self::assertNull($audit->after_json);
    }

    public function test_audit_failures_roll_back_link_update_and_remove(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $source = ImportSource::factory()->create();
        $existing = CentralBrandExternalIdentity::factory()->for($brand, 'brand')->for($source, 'source')->externalId('OLD')->create();
        $audit = $this->createMock(AuditRecorder::class);
        $audit->method('record')->willThrowException(new RuntimeException('audit unavailable'));
        $this->app->instance(AuditRecorder::class, $audit);

        foreach ([
            fn () => app(LinkCentralBrandExternalIdentityAction::class)->handle($actor, $brand, $source, 'NEW', null),
            fn () => app(UpdateCentralBrandExternalIdentityAction::class)->handle($actor, $brand, $existing, 'UPDATED', null),
            fn () => app(RemoveCentralBrandExternalIdentityAction::class)->handle($actor, $brand, $existing),
        ] as $mutation) {
            try {
                $mutation();
                self::fail('Expected audit failure.');
            } catch (RuntimeException $exception) {
                self::assertSame('audit unavailable', $exception->getMessage());
            }

            self::assertDatabaseMissing('central_brand_external_identities', ['external_id' => 'NEW']);
            self::assertDatabaseMissing('central_brand_external_identities', ['external_id' => 'UPDATED']);
            self::assertDatabaseHas('central_brand_external_identities', ['id' => $existing->id, 'external_id' => 'OLD']);
        }
    }
}
