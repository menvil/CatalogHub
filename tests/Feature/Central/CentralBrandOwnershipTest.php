<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Enums\CentralBrandStatus;
use App\Enums\UserRole;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralBrandOwnership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CentralBrandOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_shows_honest_empty_and_persisted_owner_states_while_create_stays_scalar_only(): void
    {
        $this->actingAs(User::factory()->centralAdmin()->create());
        $brand = CentralBrand::factory()->create();

        $this->get(route('central.brands.edit', $brand))
            ->assertOk()
            ->assertSee('No Parent Company assigned')
            ->assertSee('Assign existing Organization')
            ->assertSee('Create new Organization');
        $this->get(route('central.brands.create'))
            ->assertOk()
            ->assertDontSee('Ownership / Parent Company');

        $organization = Organization::factory()->create([
            'name' => str_repeat('Long Parent Company ', 10).'株式会社',
        ]);
        CentralBrandOwnership::factory()->create([
            'central_brand_id' => $brand->id,
            'organization_id' => $organization->id,
        ]);

        $this->get(route('central.brands.edit', $brand))
            ->assertOk()
            ->assertSee($organization->name)
            ->assertSee('Change Parent Company')
            ->assertSee('Clear Parent Company');
    }

    public function test_assign_replace_clear_and_create_endpoints_persist_only_the_route_brand_relation(): void
    {
        $this->actingAs(User::factory()->centralAdmin()->create());
        $brand = CentralBrand::factory()->archived()->create(['name' => 'Owned Brand']);
        $otherBrand = CentralBrand::factory()->create(['name' => 'Payload Brand']);
        $organizationA = Organization::factory()->create(['name' => 'Organization A']);
        $organizationB = Organization::factory()->create(['name' => 'Organization B']);

        $this->post(route('central.brands.ownership.assign', $brand), [
            '_ownership_operation' => 'assign',
            'organization_id' => $organizationA->id,
            'central_brand_id' => $otherBrand->id,
            'name' => 'Must not alter scalar Brand data',
        ])->assertRedirect(route('central.brands.edit', $brand));
        self::assertSame($organizationA->id, $brand->fresh()->ownership->organization_id);
        self::assertNull($otherBrand->fresh()->ownership);
        self::assertSame('Owned Brand', $brand->fresh()->name);
        self::assertSame(CentralBrandStatus::Archived, $brand->fresh()->status);

        $this->post(route('central.brands.ownership.assign', $brand), [
            '_ownership_operation' => 'assign',
            'organization_id' => $organizationB->id,
        ])->assertRedirect(route('central.brands.edit', $brand));
        self::assertSame($organizationB->id, $brand->fresh()->ownership->organization_id);

        $this->delete(route('central.brands.ownership.clear', $brand))
            ->assertRedirect(route('central.brands.edit', $brand));
        self::assertNull($brand->fresh()->ownership);
        self::assertDatabaseHas('organizations', ['id' => $organizationA->id]);
        self::assertDatabaseHas('organizations', ['id' => $organizationB->id]);

        $this->post(route('central.brands.ownership.create', $brand), [
            '_ownership_operation' => 'create',
            'organization_name' => '  新しい   親会社  ',
        ])->assertRedirect(route('central.brands.edit', $brand));
        $createdOwnership = CentralBrand::query()->findOrFail($brand->id)->ownership;
        self::assertNotNull($createdOwnership);
        self::assertSame('新しい 親会社', $createdOwnership->organization()->firstOrFail()->name);
        self::assertSame(CentralBrandStatus::Archived, $brand->fresh()->status);
    }

    public function test_validation_preserves_existing_owner_and_reopens_the_correct_modal(): void
    {
        $this->actingAs(User::factory()->centralAdmin()->create());
        $brand = CentralBrand::factory()->create();
        $organization = Organization::factory()->create();
        CentralBrandOwnership::factory()->create([
            'central_brand_id' => $brand->id,
            'organization_id' => $organization->id,
        ]);

        $response = $this->from(route('central.brands.edit', $brand))
            ->post(route('central.brands.ownership.create', $brand), [
                '_ownership_operation' => 'create',
                'organization_name' => '   ',
            ])
            ->assertRedirect(route('central.brands.edit', $brand))
            ->assertSessionHasErrors('organization_name');

        self::assertSame($organization->id, $brand->fresh()->ownership->organization_id);
        self::assertDatabaseCount('organizations', 1);
        $this->followRedirects($response)
            ->assertOk()
            ->assertSee('data-admin-modal="create-parent-company-modal"', false)
            ->assertSee('data-admin-modal-open="true"', false);

        $this->from(route('central.brands.edit', $brand))
            ->post(route('central.brands.ownership.assign', $brand), [
                '_ownership_operation' => 'assign',
                'organization_id' => 999999,
            ])
            ->assertSessionHasErrors('organization_id');
        self::assertSame($organization->id, $brand->fresh()->ownership->organization_id);
    }

    public function test_search_is_server_side_bounded_deterministic_and_does_not_embed_the_directory(): void
    {
        $this->actingAs(User::factory()->centralAdmin()->create());
        $brand = CentralBrand::factory()->create();
        foreach (range(1, 25) as $index) {
            Organization::factory()->create(['name' => sprintf('Acme Group %02d', $index)]);
        }
        Organization::factory()->create(['name' => 'Бета Холдинг']);

        $response = $this->getJson(route('central.brands.ownership.organizations.search', [
            $brand,
            'q' => 'acme',
        ]))->assertOk();
        $options = $response->json('options');
        self::assertIsArray($options);
        self::assertCount(20, $options);
        self::assertSame('Acme Group 01', $options[0]['label']);
        self::assertSame('Acme Group 20', $options[19]['label']);

        $this->getJson(route('central.brands.ownership.organizations.search', [$brand, 'q' => 'БЕТА']))
            ->assertOk()
            ->assertJsonPath('options.0.label', 'Бета Холдинг');

        $this->get(route('central.brands.edit', $brand))
            ->assertOk()
            ->assertDontSee('Acme Group 01')
            ->assertDontSee('Бета Холдинг');
    }

    public function test_all_ownership_endpoints_require_catalog_brand_management_permission(): void
    {
        $translator = User::factory()->create(['role' => UserRole::Translator]);
        $brand = CentralBrand::factory()->create();
        $organization = Organization::factory()->create();
        $this->actingAs($translator);

        foreach ([
            ['get', route('central.brands.ownership.organizations.search', $brand), []],
            ['post', route('central.brands.ownership.assign', $brand), ['organization_id' => $organization->id]],
            ['post', route('central.brands.ownership.create', $brand), ['organization_name' => 'Forbidden Org']],
            ['delete', route('central.brands.ownership.clear', $brand), []],
        ] as [$method, $url, $data]) {
            $this->{$method}($url, $data)->assertForbidden();
        }

        self::assertNull($brand->fresh()->ownership);
        self::assertDatabaseCount('organizations', 1);
    }
}
