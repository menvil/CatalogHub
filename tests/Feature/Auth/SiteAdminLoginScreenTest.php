<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Filament\Site\Pages\Auth\Login;
use App\Models\Site;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class SiteAdminLoginScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('site'));
    }

    public function test_site_login_has_distinct_identity_without_revealing_sites_or_central_identity(): void
    {
        $this->get('/admin/site/login')
            ->assertOk()
            ->assertSee('data-auth-screen="site-admin-login"', false)
            ->assertSee('CatalogHub Site Admin')
            ->assertSee('Your authorized sites appear after sign-in.')
            ->assertDontSee('CatalogHub Central')
            ->assertDontSee('Tech Germany')
            ->assertDontSee('Register')
            ->assertDontSee('Forgot your password?');
    }

    public function test_site_member_login_honors_an_authorized_intended_site_redirect(): void
    {
        $site = Site::factory()->create();
        $user = User::factory()->siteAdmin($site)->create();
        $intended = route('filament.site.pages.home', ['site_id' => $site->getKey()]);
        session()->put('url.intended', $intended);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertRedirect($intended);

        $this->assertAuthenticatedAs($user);
    }

    public function test_site_login_denies_users_without_membership_using_the_generic_failure(): void
    {
        $user = User::factory()->create(['role' => UserRole::SiteAdmin]);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }
}
