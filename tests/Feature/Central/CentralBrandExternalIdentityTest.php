<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CatalogTag;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Imports\CentralBrandExternalIdentity;
use App\Models\Imports\ImportSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class CentralBrandExternalIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_routes_and_detail_present_safe_bounded_provenance_data(): void
    {
        foreach ([
            'central.brands.external-identities.store' => ['POST', 'admin/central/brands/{brand}/external-identities'],
            'central.brands.external-identities.update' => ['PATCH', 'admin/central/brands/{brand}/external-identities/{externalIdentity}'],
            'central.brands.external-identities.destroy' => ['DELETE', 'admin/central/brands/{brand}/external-identities/{externalIdentity}'],
        ] as $name => [$method, $uri]) {
            $route = Route::getRoutes()->getByName($name);
            self::assertNotNull($route);
            self::assertContains($method, $route->methods());
            self::assertSame($uri, $route->uri());
            self::assertContains('can:catalog.brands.manage', $route->gatherMiddleware());
        }

        $brand = CentralBrand::factory()->create();
        $active = ImportSource::factory()->create([
            'name' => 'Manufacturer API',
            'code' => 'manufacturer_api',
            'config_json' => ['token' => 'super-secret-token'],
        ]);
        $inactive = ImportSource::factory()->create([
            'name' => 'Legacy Feed',
            'code' => 'legacy_feed',
            'status' => 'inactive',
            'config_json' => ['password' => 'another-secret'],
        ]);
        CentralBrandExternalIdentity::factory()->for($brand, 'brand')->for($active, 'source')
            ->externalId('brand-00142')->create(['external_url' => 'https://example.test/brands/brand-00142']);
        CentralBrandExternalIdentity::factory()->for($brand, 'brand')->for($inactive, 'source')
            ->externalId('SAMSUNG')->create(['external_url' => null]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('central.brands.show', $brand))
            ->assertOk()
            ->assertSee('data-screen-region="external-identities"', false)
            ->assertSee('Manufacturer API')
            ->assertSee('manufacturer_api')
            ->assertSee('brand-00142')
            ->assertSee('Open record')
            ->assertSee('Legacy Feed')
            ->assertSee('Inactive')
            ->assertSee('SAMSUNG')
            ->assertSee('No external URL')
            ->assertDontSee('super-secret-token')
            ->assertDontSee('another-secret');

        /** @var CentralBrand $viewBrand */
        $viewBrand = $response->viewData('brand');
        self::assertTrue($viewBrand->relationLoaded('externalIdentities'));
        self::assertFalse($viewBrand->externalIdentities->firstOrFail()->source->offsetExists('config_json'));
    }

    public function test_http_store_update_and_delete_stay_on_brand_detail_with_request_audit_context(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->archived()->create();
        $source = ImportSource::factory()->create(['code' => 'manufacturer_api']);

        $this->actingAs($actor)
            ->withHeader('X-Request-ID', 'external-identity-request')
            ->post(route('central.brands.external-identities.store', $brand), [
                'import_source_id' => $source->id,
                'external_id' => '  000123  ',
                'external_url' => 'https://example.test/brands/000123',
            ])
            ->assertRedirect(route('central.brands.show', $brand).'#external-identities')
            ->assertSessionHas('success', 'External identity linked.');

        $identity = $brand->externalIdentities()->sole();
        self::assertSame('000123', $identity->external_id);
        self::assertSame('archived', $brand->fresh()->status->value);
        self::assertSame('external-identity-request', AuditLogEntry::query()->sole()->request_id);

        $this->patch(route('central.brands.external-identities.update', [$brand, $identity]), [
            '_external_identity_id' => $identity->id,
            'external_id' => '000124',
            'external_url' => '',
        ])->assertRedirect(route('central.brands.show', $brand).'#external-identities')
            ->assertSessionHas('success', 'External identity updated.');
        self::assertDatabaseHas('central_brand_external_identities', [
            'id' => $identity->id,
            'external_id' => '000124',
            'external_url' => null,
        ]);

        $this->delete(route('central.brands.external-identities.destroy', [$brand, $identity]))
            ->assertRedirect(route('central.brands.show', $brand).'#external-identities')
            ->assertSessionHas('success', 'External identity removed.');
        self::assertDatabaseMissing('central_brand_external_identities', ['id' => $identity->id]);
        self::assertDatabaseHas('import_sources', ['id' => $source->id]);
        self::assertSame([
            AuditAction::CatalogBrandExternalIdentityLinked->value,
            AuditAction::CatalogBrandExternalIdentityUpdated->value,
            AuditAction::CatalogBrandExternalIdentityUnlinked->value,
        ], AuditLogEntry::query()->orderBy('id')->pluck('action')->all());
    }

    public function test_store_trims_external_id_before_length_validation(): void
    {
        $brand = CentralBrand::factory()->create();
        $source = ImportSource::factory()->create();
        $externalId = str_repeat('x', 255);

        $this->actingAs(User::factory()->create())
            ->post(route('central.brands.external-identities.store', $brand), [
                'import_source_id' => $source->id,
                'external_id' => '  '.$externalId.'  ',
            ])
            ->assertRedirect(route('central.brands.show', $brand).'#external-identities')
            ->assertSessionDoesntHaveErrors();

        self::assertDatabaseHas('central_brand_external_identities', [
            'central_brand_id' => $brand->id,
            'external_id' => $externalId,
            'external_id_hash' => hash('sha256', $externalId),
        ]);
    }

    #[DataProvider('externalIdsWithBoundaryControlCharacters')]
    public function test_requests_reject_boundary_control_characters_before_trimming(string $externalId): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $source = ImportSource::factory()->create();

        $this->actingAs($actor)
            ->post(route('central.brands.external-identities.store', $brand), [
                'import_source_id' => $source->id,
                'external_id' => $externalId,
            ])
            ->assertSessionHasErrors('external_id');

        $identity = CentralBrandExternalIdentity::factory()
            ->for($brand, 'brand')->for($source, 'source')->externalId('PERSISTED')->create();

        $this->patch(route('central.brands.external-identities.update', [$brand, $identity]), [
            '_external_identity_id' => $identity->id,
            'external_id' => $externalId,
        ])->assertSessionHasErrors('external_id');

        self::assertSame('PERSISTED', $identity->fresh()->external_id);
        self::assertDatabaseCount('central_brand_external_identities', 1);
        self::assertDatabaseCount('audit_log_entries', 0);
    }

    public static function externalIdsWithBoundaryControlCharacters(): iterable
    {
        yield 'leading NUL' => ["\0external"];
        yield 'trailing NUL' => ["external\0"];
        yield 'leading vertical tab' => ["\vexternal"];
        yield 'trailing vertical tab' => ["external\v"];
    }

    public function test_validation_reopens_only_relevant_modal_with_old_input_and_no_mutation(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $source = ImportSource::factory()->create();
        $identity = CentralBrandExternalIdentity::factory()->for($brand, 'brand')->for($source, 'source')->externalId('PERSISTED')->create();

        $invalidAdd = $this->actingAs($actor)
            ->from(route('central.brands.show', $brand))
            ->post(route('central.brands.external-identities.store', $brand), [
                '_external_identity_operation' => 'add',
                'import_source_id' => $source->id,
                'external_id' => 'TEMPORARY',
                'external_url' => 'javascript:alert(1)',
            ])
            ->assertRedirect(route('central.brands.show', $brand))
            ->assertSessionHasErrors('external_url')
            ->assertSessionHasInput('external_id', 'TEMPORARY');

        $this->followRedirects($invalidAdd)
            ->assertOk()
            ->assertSee('data-admin-modal="add-brand-external-identity-modal"', false)
            ->assertSee('data-admin-modal-open="true"', false)
            ->assertSee('javascript:alert(1)')
            ->assertSee('without embedded credentials');

        self::assertSame('PERSISTED', $identity->fresh()->external_id);
        self::assertDatabaseCount('audit_log_entries', 0);
    }

    public function test_edit_validation_reopens_only_the_target_identity_modal(): void
    {
        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $source = ImportSource::factory()->create();
        $identity = CentralBrandExternalIdentity::factory()->for($brand, 'brand')->for($source, 'source')->externalId('PERSISTED')->create();

        $invalidEdit = $this->actingAs($actor)
            ->from(route('central.brands.show', $brand))
            ->patch(route('central.brands.external-identities.update', [$brand, $identity]), [
                '_external_identity_id' => $identity->id,
                'external_id' => 'EDITED',
                'external_url' => 'https://user:pass@example.test/record',
            ])
            ->assertSessionHasErrors('external_url')
            ->assertSessionHasInput('external_id', 'EDITED')
            ->assertSessionHasInput('_external_identity_id', (string) $identity->id);

        $this->followRedirects($invalidEdit)
            ->assertOk()
            ->assertSee('data-admin-modal="edit-brand-external-identity-'.$identity->id.'-modal"', false)
            ->assertSee('data-admin-modal-open="true"', false)
            ->assertSee('data-admin-modal-reset-value="PERSISTED"', false);

        self::assertSame('PERSISTED', $identity->fresh()->external_id);
        self::assertDatabaseCount('audit_log_entries', 0);
    }

    public function test_inactive_source_cannot_be_linked_and_empty_states_are_honest(): void
    {
        $brand = CentralBrand::factory()->create();
        $inactive = ImportSource::factory()->create(['status' => 'inactive']);

        $this->actingAs(User::factory()->create())
            ->post(route('central.brands.external-identities.store', $brand), [
                'import_source_id' => $inactive->id,
                'external_id' => '123',
            ])
            ->assertSessionHasErrors('import_source_id');

        $this->get(route('central.brands.show', $brand))
            ->assertOk()
            ->assertSee('No external identities are linked to this Brand.')
            ->assertSee('No active import sources are available.')
            ->assertDontSee('Add identity');
        self::assertDatabaseCount('central_brand_external_identities', 0);
    }

    public function test_unauthorized_and_cross_brand_nested_routes_cannot_mutate_or_audit(): void
    {
        $brandA = CentralBrand::factory()->create();
        $brandB = CentralBrand::factory()->create();
        $source = ImportSource::factory()->create();
        $identity = CentralBrandExternalIdentity::factory()->for($brandB, 'brand')->for($source, 'source')->externalId('B-ID')->create();
        $translator = User::factory()->create(['role' => UserRole::Translator]);

        $this->actingAs($translator)
            ->post(route('central.brands.external-identities.store', $brandA), [
                'import_source_id' => $source->id,
                'external_id' => 'FORBIDDEN',
            ])->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->patch(route('central.brands.external-identities.update', [$brandA, $identity]), [
                '_external_identity_id' => $identity->id,
                'external_id' => 'TAMPERED',
            ])->assertNotFound();
        $this->delete(route('central.brands.external-identities.destroy', [$brandA, $identity]))
            ->assertNotFound();

        self::assertSame('B-ID', $identity->fresh()->external_id);
        self::assertDatabaseCount('central_brand_external_identities', 1);
        self::assertDatabaseCount('audit_log_entries', 0);
    }

    public function test_external_identity_changes_leave_tags_unchanged(): void
    {
        $brand = CentralBrand::factory()->create();
        $tag = CatalogTag::factory()->create(['name' => 'Premium']);
        $brand->tags()->attach($tag);
        $source = ImportSource::factory()->create();

        $this->actingAs(User::factory()->create())->post(route('central.brands.external-identities.store', $brand), [
            'import_source_id' => $source->id,
            'external_id' => '123',
        ])->assertRedirect();

        self::assertSame(['Premium'], $brand->fresh()->tags()->pluck('name')->all());
    }
}
