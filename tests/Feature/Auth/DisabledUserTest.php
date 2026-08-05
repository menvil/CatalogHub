<?php

namespace Tests\Feature\Auth;

use App\Models\Site;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DisabledUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_user_cannot_log_in_to_the_central_panel(): void
    {
        $user = User::factory()->centralAdmin()->create(['disabled_at' => now()]);
        Filament::setCurrentPanel(Filament::getPanel('central'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_user_disabled_after_login_is_logged_out_on_the_next_panel_request(): void
    {
        $user = User::factory()->centralAdmin()->create();
        $this->actingAs($user);
        $user->update(['disabled_at' => now()]);

        $this->get('/admin/central')->assertRedirect('/admin/central/login');

        $this->assertGuest();
    }

    public function test_disabled_site_member_is_denied_and_active_users_are_unaffected(): void
    {
        $site = Site::factory()->create();
        $disabled = User::factory()->siteAdmin($site)->create(['disabled_at' => now()]);

        $this->actingAs($disabled)
            ->get('/admin/site')
            ->assertRedirect('/admin/site/login');
        $this->assertGuest();

        $active = User::factory()->centralAdmin()->create();
        $this->actingAs($active)->get('/admin/central')->assertOk();
    }
}
