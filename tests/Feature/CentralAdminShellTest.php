<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentralAdminShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_central_dashboard_placeholder(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('CatalogHub')
            ->assertSee('Central Dashboard placeholder');
    }

    public function test_guest_is_redirected_from_central_dashboard(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }
}
