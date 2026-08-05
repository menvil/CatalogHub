<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CentralLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_home_renders_the_single_semantic_shell_frame(): void
    {
        $response = $this->actingAs(User::factory()->centralAdmin()->create())
            ->get('/admin/central');

        $response
            ->assertOk()
            ->assertSee('data-central-shell', false)
            ->assertSee('data-central-sidebar', false)
            ->assertSee('data-central-header', false)
            ->assertSee('id="central-main-content"', false)
            ->assertSee('aria-label="Central Admin navigation"', false)
            ->assertDontSee('data-presentation-context="site-admin"', false)
            ->assertDontSee('data-presentation-context="public-site"', false);
    }
}
