<?php

declare(strict_types=1);

namespace Tests\Feature\SiteAdmin;

use App\Enums\UserRole;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Tests\TestCase;

final class SiteRouteBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_the_site_login(): void
    {
        $this->get('/admin/site')->assertRedirect('/admin/site/login');
    }

    public function test_site_admin_opens_a_shell_bound_to_their_temporary_site_context(): void
    {
        $site = Site::factory()->create(['name' => 'Boundary fixture site']);
        $otherSite = Site::factory()->create(['name' => 'Other tenant site']);
        $user = User::factory()->siteAdmin($site)->create();

        $this->actingAs($user)
            ->get("/admin/site?site_id={$otherSite->id}")
            ->assertOk()
            ->assertSee('Site Admin shell')
            ->assertSee('Boundary fixture site')
            ->assertDontSee('Other tenant site')
            ->assertSee('data-presentation-context="site-admin"', false);
    }

    public function test_central_only_user_has_no_implicit_site_access(): void
    {
        $user = User::factory()->create(['role' => UserRole::CentralAdmin]);

        $this->actingAs($user)->get('/admin/site')->assertForbidden();
    }

    public function test_site_route_names_are_unique_and_owned_by_the_boundary(): void
    {
        $names = collect(app('router')->getRoutes()->getRoutes())
            ->map(static fn (Route $route): ?string => $route->getName())
            ->filter(static fn (?string $name): bool => is_string($name) && str_starts_with($name, 'filament.site.'));

        self::assertNotEmpty($names);
        self::assertSame($names->count(), $names->unique()->count());
    }
}
