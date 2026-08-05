<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CentralHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_renders_truthful_slots_and_the_authenticated_user_menu(): void
    {
        $user = User::factory()->centralAdmin()->create(['name' => 'Central Operator']);

        $this->actingAs($user)
            ->get('/admin/central')
            ->assertOk()
            ->assertSee('Central Operator')
            ->assertSee('data-central-user-menu', false)
            ->assertSee('Search unavailable')
            ->assertSee('Notifications unavailable')
            ->assertSee('action="'.route('filament.central.auth.logout', absolute: false).'"', false);
    }

    public function test_header_and_navigation_respect_the_current_role(): void
    {
        $translator = User::factory()->create(['role' => UserRole::Translator]);

        $this->actingAs($translator)
            ->get('/admin/central')
            ->assertOk()
            ->assertSee('Translations')
            ->assertDontSee('>Media<', false)
            ->assertDontSee('>Snapshots<', false);
    }
}
