<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CentralDashboardShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_dashboard_requires_central_access(): void
    {
        $this->get('/admin/central')->assertRedirect('/admin/central/login');

        $this->actingAs(User::factory()->create(['role' => UserRole::SiteAdmin]))
            ->get('/admin/central')
            ->assertForbidden();
    }

    public function test_ca_001_is_a_neutral_foundation_state_without_fake_metrics(): void
    {
        $response = $this->actingAs(User::factory()->centralAdmin()->create())
            ->get('/admin/central');

        $response
            ->assertOk()
            ->assertSee('data-screen-id="CA-001"', false)
            ->assertSee('data-admin-empty-state="default"', false)
            ->assertSee('No metrics are available in the foundation shell.')
            ->assertDontSee('0 products')
            ->assertDontSee('0 imports')
            ->assertDontSee('0 users');
    }
}
