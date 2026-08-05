<?php

namespace Tests\Feature\Auth;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Site;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_middleware_denies_a_user_missing_the_page_permission(): void
    {
        Route::middleware(['web', 'cataloghub.permission:central.page.access'])
            ->get('/_authorization/central-page', fn () => response('authorized page'));

        $siteAdmin = User::factory()->create(['role' => UserRole::SiteAdmin]);

        $this->actingAs($siteAdmin)
            ->get('/_authorization/central-page')
            ->assertForbidden()
            ->assertDontSee('authorized page');

        $centralAdmin = User::factory()->centralAdmin()->create();

        $this->actingAs($centralAdmin)
            ->get('/_authorization/central-page')
            ->assertOk()
            ->assertSee('authorized page');
    }

    public function test_site_action_requires_permission_and_membership(): void
    {
        $assigned = Site::factory()->create();
        $other = Site::factory()->create();
        $user = User::factory()->siteAdmin($assigned)->create();
        $authorization = app(AuthorizationService::class);

        $authorization->authorizeMutation($user, Permission::SiteMutationExecute, $assigned);

        $this->expectException(AuthorizationException::class);
        $authorization->authorizeMutation($user, Permission::SiteMutationExecute, $other);
    }

    public function test_forbidden_mutation_callback_has_no_database_side_effect(): void
    {
        $user = User::factory()->create(['role' => UserRole::SiteAdmin]);
        $before = Site::query()->count();

        try {
            app(AuthorizationService::class)->runMutation(
                $user,
                Permission::CentralMutationExecute,
                fn () => Site::factory()->create(),
            );
            self::fail('The forbidden mutation was executed.');
        } catch (AuthorizationException) {
            self::assertSame($before, Site::query()->count());
        }
    }
}
