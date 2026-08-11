<?php

declare(strict_types=1);

namespace Tests\Feature\Acceptance;

use App\Models\AuditLogEntry;
use App\Models\Site;
use App\Models\SiteMembership;
use App\Models\User;
use Database\Seeders\FoundationDemoSeeder;
use Database\Seeders\SiteFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CrossContextSecurityAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(FoundationDemoSeeder::class);
    }

    public function test_site_and_central_personas_cannot_cross_panel_boundaries_without_side_effects(): void
    {
        $before = $this->sideEffects();
        $siteAdmin = $this->user('site-admin@demo.cataloghub.test');
        $centralAdmin = $this->user('central-admin@demo.cataloghub.test');

        $this->actingAs($siteAdmin)
            ->get('/admin/central')
            ->assertForbidden()
            ->assertDontSee('data-screen-id="CA-001"', false)
            ->assertDontSee($siteAdmin->email);

        $this->actingAs($centralAdmin)
            ->get('/admin/site')
            ->assertForbidden()
            ->assertDontSee('data-screen-id="SA-001"', false)
            ->assertDontSee('Tech Germany')
            ->assertDontSee('Monitors Germany');

        self::assertSame($before, $this->sideEffects());
    }

    public function test_site_id_tampering_and_archived_context_are_denied_without_existence_leaks(): void
    {
        $before = $this->sideEffects();
        $translator = $this->user('translator@demo.cataloghub.test');
        $monitors = $this->site('monitors-germany');
        $archived = $this->site('archived-germany');

        foreach ([$monitors, $archived] as $forbiddenSite) {
            $this->actingAs($translator)
                ->get('/admin/site?site_id='.$forbiddenSite->getKey())
                ->assertForbidden()
                ->assertDontSee($forbiddenSite->name)
                ->assertDontSee($forbiddenSite->domain);
        }

        self::assertSame($before, $this->sideEffects());
    }

    public function test_disabled_persona_is_logged_out_from_both_admin_contexts(): void
    {
        $disabled = $this->user('disabled@demo.cataloghub.test');

        $this->actingAs($disabled)
            ->get('/admin/central')
            ->assertRedirect('/admin/central/login');
        $this->assertGuest();

        $this->actingAs($disabled)
            ->get('/admin/site')
            ->assertRedirect('/admin/site/login');
        $this->assertGuest();
    }

    public function test_unknown_and_archived_public_hosts_return_safe_not_found_responses(): void
    {
        foreach (['unknown.cataloghub.test', SiteFoundationSeeder::ARCHIVED_HOST] as $host) {
            $this->get("http://{$host}/de-DE")
                ->assertNotFound()
                ->assertDontSee('Tech Germany')
                ->assertDontSee('Monitors Germany')
                ->assertDontSee('database')
                ->assertDontSee('No available site is configured');
        }
    }

    public function test_authenticated_admin_session_never_changes_the_public_presentation_context(): void
    {
        $centralAdmin = $this->user('central-admin@demo.cataloghub.test');

        $this->actingAs($centralAdmin)
            ->get('http://'.SiteFoundationSeeder::TECH_HOST.'/de-DE')
            ->assertOk()
            ->assertSee('data-presentation-context="public-site"', false)
            ->assertSee('data-public-layout="multi-category"', false)
            ->assertDontSee('data-central-shell', false)
            ->assertDontSee('data-site-shell', false)
            ->assertDontSee($centralAdmin->email);
    }

    private function user(string $email): User
    {
        return User::query()->where('email', $email)->sole();
    }

    private function site(string $code): Site
    {
        return Site::query()->where('code', $code)->sole();
    }

    /** @return array{sites: int, memberships: int, audits: int} */
    private function sideEffects(): array
    {
        return [
            'sites' => Site::query()->count(),
            'memberships' => SiteMembership::query()->count(),
            'audits' => AuditLogEntry::query()->count(),
        ];
    }
}
