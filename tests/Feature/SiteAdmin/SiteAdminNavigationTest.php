<?php

declare(strict_types=1);

namespace Tests\Feature\SiteAdmin;

use App\Models\Site;
use App\Models\SiteMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SiteAdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shell_exposes_context_specific_accessible_navigation_controls(): void
    {
        $site = Site::factory()->active()->withRuntimeContext()->create();
        $otherSite = Site::factory()->active()->withRuntimeContext()->create();
        $user = User::factory()->siteAdmin($site)->create();
        SiteMembership::factory()->for($user)->for($otherSite)->create();

        $this->actingAs($user)
            ->get(route('filament.site.pages.home', ['site_id' => $site->getKey()]))
            ->assertOk()
            ->assertSee('data-site-sidebar-open', false)
            ->assertSee('aria-controls="site-navigation"', false)
            ->assertSee('data-site-sidebar-collapse', false)
            ->assertSee('data-site-sidebar-preference="local"', false)
            ->assertSee('data-site-sidebar-mobile-open="false"', false)
            ->assertSee('data-site-selector-link', false)
            ->assertSee('data-site-sidebar-current', false)
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('data-central-sidebar-open', false);
    }
}
