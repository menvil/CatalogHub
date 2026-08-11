<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Filament\Central\Pages\Auth\Login as CentralLogin;
use App\Http\Middleware\ResolveSiteRuntimeContext;
use App\Models\AuditLogEntry;
use App\Models\SiteDomain;
use App\Models\SiteMembership;
use App\Models\User;
use App\Support\Sites\SiteRuntimeContext;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Support\FoundationVisualFixture;
use Tests\Support\SiteFixtures;
use Tests\TestCase;

final class SecurityContextSuiteTest extends TestCase
{
    use RefreshDatabase;
    use SiteFixtures;

    public function test_central_login_accepts_an_active_operator(): void
    {
        $operator = User::factory()->centralAdmin()->create(FoundationVisualFixture::centralAdmin());
        Filament::setCurrentPanel(Filament::getPanel('central'));

        Livewire::test(CentralLogin::class)
            ->fillForm(['email' => $operator->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertRedirect('/admin/central');

        $this->assertAuthenticatedAs($operator);
    }

    public function test_disabled_operator_is_rejected_without_authentication(): void
    {
        $operator = User::factory()->centralAdmin()->disabled()->create(FoundationVisualFixture::centralAdmin());
        Filament::setCurrentPanel(Filament::getPanel('central'));

        Livewire::test(CentralLogin::class)
            ->fillForm(['email' => $operator->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_panel_access_and_site_id_tampering_are_denied_without_side_effects(): void
    {
        $assigned = $this->foundationSite('alpha');
        $other = $this->foundationSite('beta');
        $owner = User::factory()->siteOwner($assigned)->create();
        $membershipCount = SiteMembership::query()->count();
        $auditCount = AuditLogEntry::query()->count();

        $this->actingAs($owner)
            ->get("/admin/site?site_id={$other->getKey()}")
            ->assertForbidden()
            ->assertDontSee($other->name);

        self::assertSame($membershipCount, SiteMembership::query()->count());
        self::assertSame($auditCount, AuditLogEntry::query()->count());
        self::assertSame('fixture-alpha', $owner->fresh()?->site?->code);
    }

    public function test_alias_host_resolves_and_unknown_host_is_rejected(): void
    {
        $site = $this->foundationSite('alpha', ['en-US', 'de-DE']);
        SiteDomain::factory()->alias()->for($site)->create(['host' => 'alias.fixture-alpha.test']);
        $this->registerContextProbe();

        $this->get('http://alias.fixture-alpha.test/_foundation/de-DE')
            ->assertOk()
            ->assertJsonPath('site', 'fixture-alpha')
            ->assertJsonPath('host', 'alias.fixture-alpha.test')
            ->assertJsonPath('locale', 'de-DE');

        $this->get('http://unknown.fixture.test/_foundation/en-US')->assertNotFound();
    }

    public function test_unsupported_locale_falls_back_to_the_site_default(): void
    {
        $this->foundationSite('alpha', ['en-US', 'de-DE']);
        $this->registerContextProbe();

        $this->get('http://fixture-alpha.test/_foundation/fr-FR')
            ->assertOk()
            ->assertJsonPath('requested_locale', 'fr-FR')
            ->assertJsonPath('locale', 'en-US');
    }

    private function registerContextProbe(): void
    {
        Route::middleware(['web', ResolveSiteRuntimeContext::class])
            ->get('/_foundation/{locale?}', static fn (SiteRuntimeContext $context): array => [
                'site' => $context->site->code,
                'host' => $context->domain->host,
                'requested_locale' => $context->requestedLocale,
                'locale' => $context->resolvedLocale,
            ]);
    }
}
