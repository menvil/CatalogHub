<?php

namespace Tests\Feature\Auth;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Site;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\AuthFixtures;
use Tests\TestCase;

class AuthorizationMatrixTest extends TestCase
{
    use AuthFixtures;
    use RefreshDatabase;

    #[DataProvider('panelMatrixProvider')]
    public function test_foundation_role_panel_matrix(
        UserRole $role,
        bool $centralAllowed,
        bool $siteAllowed,
    ): void {
        $site = $this->authorizationSite('Matrix site');
        $user = $this->userForRole($role, $siteAllowed ? $site : null);

        $central = $this->actingAs($user)->get('/admin/central');
        $siteResponse = $this->actingAs($user)->get("/admin/site?site_id={$site->getKey()}");

        $centralAllowed ? $central->assertOk() : $central->assertForbidden();
        $siteAllowed ? $siteResponse->assertOk() : $siteResponse->assertForbidden();
    }

    #[DataProvider('siteRoleProvider')]
    public function test_site_roles_without_membership_are_denied(UserRole $role): void
    {
        $site = $this->authorizationSite('Unassigned site');
        $user = $this->userForRole($role);

        $this->actingAs($user)
            ->get("/admin/site?site_id={$site->getKey()}")
            ->assertForbidden();
    }

    #[DataProvider('siteRoleProvider')]
    public function test_site_roles_cannot_tamper_into_a_second_site(UserRole $role): void
    {
        $assigned = $this->authorizationSite('Assigned matrix site');
        $other = $this->authorizationSite('Other matrix site');
        $user = $this->userForRole($role, $assigned);

        $this->actingAs($user)
            ->get("/admin/site?site_id={$assigned->getKey()}")
            ->assertOk();
        $this->get("/admin/site?site_id={$other->getKey()}")
            ->assertForbidden()
            ->assertDontSee('Other matrix site');
    }

    public function test_disabled_users_are_denied_in_both_contexts(): void
    {
        $site = $this->authorizationSite('Disabled matrix site');
        $central = $this->userForRole(UserRole::CentralAdmin);
        $siteAdmin = $this->userForRole(UserRole::SiteAdmin, $site);
        $central->update(['disabled_at' => now()]);
        $siteAdmin->update(['disabled_at' => now()]);

        $this->actingAs($central)->get('/admin/central')->assertRedirect('/admin/central/login');
        $this->actingAs($siteAdmin)->get('/admin/site')->assertRedirect('/admin/site/login');
    }

    public function test_cross_site_forbidden_mutation_has_no_side_effect(): void
    {
        $assigned = $this->authorizationSite('Mutation site');
        $other = $this->authorizationSite('Forbidden mutation site');
        $user = $this->userForRole(UserRole::SiteAdmin, $assigned);
        $before = Site::query()->count();

        try {
            app(AuthorizationService::class)->runMutation(
                $user,
                Permission::SiteMutationExecute,
                fn () => Site::factory()->create(),
                $other,
            );
            self::fail('Cross-site mutation was executed.');
        } catch (AuthorizationException) {
            self::assertSame($before, Site::query()->count());
        }
    }

    public function test_auth_fixture_rejects_roles_without_a_site_membership_mapping(): void
    {
        $site = $this->authorizationSite('Unsupported membership role site');
        $before = User::query()->count();

        try {
            $this->userForRole(UserRole::CatalogEditor, $site);
            self::fail('Catalog Editor was mapped to a Site membership role.');
        } catch (InvalidArgumentException) {
            self::assertSame($before, User::query()->count());
        }
    }

    public function test_authorization_document_matches_the_foundation_contract(): void
    {
        $path = base_path('docs/architecture/authorization.md');

        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        foreach (['central.panel.access', 'site.panel.access', 'active membership', 'disabled_at'] as $contract) {
            $this->assertStringContainsString($contract, $contents);
        }
    }

    /** @return array<string, array{UserRole, bool, bool}> */
    public static function panelMatrixProvider(): array
    {
        return [
            'super admin' => [UserRole::SuperAdmin, true, true],
            'central admin' => [UserRole::CentralAdmin, true, false],
            'catalog editor' => [UserRole::CatalogEditor, true, false],
            'site admin' => [UserRole::SiteAdmin, false, true],
            'translator' => [UserRole::Translator, true, true],
            'moderator' => [UserRole::Moderator, false, true],
        ];
    }

    /** @return array<string, array{UserRole}> */
    public static function siteRoleProvider(): array
    {
        return [
            'super admin' => [UserRole::SuperAdmin],
            'site admin' => [UserRole::SiteAdmin],
            'translator' => [UserRole::Translator],
            'moderator' => [UserRole::Moderator],
        ];
    }
}
