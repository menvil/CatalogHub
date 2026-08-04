<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Contracts\Auth\CentralAdminAccess;
use App\Contracts\Auth\LegacySiteAdminRouteAccess;
use App\Contracts\Auth\SiteAdminAccess;
use App\Enums\UserRole;
use App\Http\Middleware\EnsureCentralAdminAccess;
use App\Http\Middleware\EnsureSiteAdminAccess;
use App\Models\Site;
use App\Models\User;
use App\Policies\CentralPanelPolicy;
use App\Support\Auth\TemporaryLegacySiteAdminRouteAccess;
use App\Support\Auth\TemporarySiteAdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ContextAccessMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_and_site_access_contracts_are_distinct_bindings(): void
    {
        self::assertInstanceOf(CentralPanelPolicy::class, app(CentralAdminAccess::class));
        self::assertInstanceOf(TemporaryLegacySiteAdminRouteAccess::class, app(LegacySiteAdminRouteAccess::class));
        self::assertInstanceOf(TemporarySiteAdminAccess::class, app(SiteAdminAccess::class));
    }

    public function test_wrong_context_is_rejected_server_side_before_the_endpoint_runs(): void
    {
        Route::middleware(['web', EnsureCentralAdminAccess::class])
            ->get('/_boundary/central', fn () => response('central endpoint'));
        Route::middleware(['web', EnsureSiteAdminAccess::class])
            ->get('/_boundary/site', fn () => response('site endpoint'));

        $site = Site::factory()->create();
        $siteAdmin = User::factory()->siteAdmin($site)->create();
        $centralAdmin = User::factory()->create(['role' => UserRole::CentralAdmin]);

        $this->actingAs($siteAdmin)
            ->get('/_boundary/central')
            ->assertForbidden()
            ->assertDontSee('central endpoint');

        $this->actingAs($centralAdmin)
            ->get('/_boundary/site')
            ->assertForbidden()
            ->assertDontSee('site endpoint');
    }

    public function test_allowed_roles_open_only_their_context_shell(): void
    {
        $site = Site::factory()->create();
        $siteAdmin = User::factory()->siteAdmin($site)->create();
        $centralAdmin = User::factory()->create(['role' => UserRole::CentralAdmin]);

        $this->actingAs($centralAdmin)->get('/admin/central')->assertOk();
        $this->get('/admin/site')->assertForbidden();

        $this->actingAs($siteAdmin)->get('/admin/site')->assertOk();
        $this->get('/admin/central')->assertForbidden();
    }

    public function test_public_context_remains_accessible_without_context_access_middleware(): void
    {
        $route = app('router')->getRoutes()->getByName('public.home');

        self::assertNotNull($route);
        self::assertNotContains(EnsureCentralAdminAccess::class, $route->gatherMiddleware());
        self::assertNotContains(EnsureSiteAdminAccess::class, $route->gatherMiddleware());
    }
}
