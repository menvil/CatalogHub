<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Filament\Central\Pages\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class CentralLoginScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('central'));
    }

    public function test_central_login_has_only_central_identity_and_no_self_service_links(): void
    {
        $this->get('/admin/central/login')
            ->assertOk()
            ->assertSee('data-auth-screen="central-login"', false)
            ->assertSee('CatalogHub Central')
            ->assertSee('Authorized platform operators only.')
            ->assertDontSee('CatalogHub Site')
            ->assertDontSee('Register')
            ->assertDontSee('Forgot your password?');
    }

    public function test_central_login_accepts_authorized_user_and_honors_intended_redirect(): void
    {
        $user = User::factory()->centralAdmin()->create();
        $intended = route('central.component-gallery');
        session()->put('url.intended', $intended);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertRedirect($intended);

        $this->assertAuthenticatedAs($user);
    }

    public function test_central_login_uses_the_same_generic_failure_for_invalid_and_disabled_accounts(): void
    {
        $disabled = User::factory()->centralAdmin()->create(['disabled_at' => now()]);
        $messages = [];

        foreach ([
            ['email' => 'unknown@example.test', 'password' => 'incorrect'],
            ['email' => $disabled->email, 'password' => 'password'],
        ] as $credentials) {
            $component = Livewire::test(Login::class)
                ->fillForm($credentials)
                ->call('authenticate')
                ->assertHasFormErrors(['email']);

            $message = $component->errors()->first('email');
            $this->assertIsString($message);
            $messages[] = $message;

            $this->assertGuest();
        }

        $this->assertSame($messages[0], $messages[1]);
    }
}
