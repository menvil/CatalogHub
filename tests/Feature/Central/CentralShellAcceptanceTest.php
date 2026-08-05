<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class CentralShellAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_preview_exposes_deterministic_shell_states(): void
    {
        foreach (['default', 'collapsed', 'mobile', 'long-header'] as $state) {
            $this->get('/dev/central-shell?state='.$state)
                ->assertOk()
                ->assertSee('data-central-shell-fixture="central-shell-v1"', false)
                ->assertSee('data-central-preview-state="'.$state.'"', false)
                ->assertSee('Central Acceptance User')
                ->assertSee('data-screen-id="CA-001"', false);
        }
    }

    public function test_login_dashboard_navigation_and_logout_flow(): void
    {
        $user = User::factory()->centralAdmin()->create([
            'email' => 'central.acceptance@example.test',
            'password' => 'central-acceptance-password',
        ]);
        Filament::setCurrentPanel(Filament::getPanel('central'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'central-acceptance-password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->get('/admin/central')
            ->assertOk()
            ->assertSee('data-screen-id="CA-001"', false)
            ->assertSee('href="'.route('central.media.index', absolute: false).'"', false);

        $this->post('/admin/central/logout')->assertRedirect('/admin/central/login');
        $this->assertGuest();
    }
}
