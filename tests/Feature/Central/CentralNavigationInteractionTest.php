<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CentralNavigationInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_shell_exposes_accessible_mobile_and_desktop_navigation_controls(): void
    {
        $user = User::factory()->centralAdmin()->create();

        $this->actingAs($user)
            ->get('/admin/central')
            ->assertOk()
            ->assertSee('data-central-sidebar-open', false)
            ->assertSee('aria-controls="central-navigation"', false)
            ->assertSee('data-central-sidebar-collapse', false)
            ->assertSee('data-central-sidebar-preference="local"', false)
            ->assertSee('data-central-sidebar-mobile-open="false"', false)
            ->assertSee('aria-current="page"', false);
    }
}
