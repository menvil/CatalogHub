<?php

declare(strict_types=1);

namespace Tests\Feature\SiteAdmin;

use App\Models\Site;
use App\Models\SiteMembership;
use App\Models\User;
use App\Support\DesignSystem\SiteAdminShellFixture;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class SiteAdminShellAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_preview_exposes_deterministic_one_and_multi_site_states(): void
    {
        foreach (SiteAdminShellFixture::STATES as $state) {
            $response = $this->get('/dev/site-admin-shell?state='.$state)
                ->assertOk()
                ->assertSee('data-site-admin-shell-fixture="'.SiteAdminShellFixture::VERSION.'"', false)
                ->assertSee('data-site-preview-state="'.$state.'"', false)
                ->assertSee('Site Acceptance User')
                ->assertSee('data-screen-id="SA-001"', false)
                ->assertSee('Tech Germany');

            if ($state === 'one-site') {
                $response->assertSee('Only assigned site')->assertDontSee('Monitors Germany');
            } else {
                $response->assertSee('Monitors Germany');
            }
        }
    }

    public function test_login_site_switch_denied_site_and_logout_flow(): void
    {
        $first = Site::factory()->active()->withRuntimeContext()->create(['name' => 'Acceptance first']);
        $second = Site::factory()->active()->withRuntimeContext()->create(['name' => 'Acceptance second']);
        $denied = Site::factory()->active()->withRuntimeContext()->create(['name' => 'Acceptance denied']);
        $user = User::factory()->siteAdmin($first)->create([
            'email' => 'site.shell.acceptance@example.test',
            'password' => 'site-shell-acceptance-password',
        ]);
        SiteMembership::factory()->for($user)->for($second)->create();
        Filament::setCurrentPanel(Filament::getPanel('site'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'site-shell-acceptance-password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->get(route('filament.site.pages.home', ['site_id' => $first->getKey()]))
            ->assertOk()
            ->assertSee('Acceptance first')
            ->assertSee('data-screen-id="SA-001"', false);

        $this->get(route('filament.site.pages.home', ['site_id' => $second->getKey()]))
            ->assertOk()
            ->assertSee('Acceptance second');

        $this->get(route('filament.site.pages.home', ['site_id' => $denied->getKey()]))
            ->assertForbidden()
            ->assertDontSee('Acceptance denied');

        $this->post('/admin/site/logout')->assertRedirect('/admin/site/login');
        $this->assertGuest();
    }
}
