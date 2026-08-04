<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Tests\TestCase;

final class CentralRouteBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_the_central_login(): void
    {
        $this->get('/admin/central')->assertRedirect('/admin/central/login');
    }

    public function test_allowed_user_opens_the_empty_central_shell(): void
    {
        $user = User::factory()->create(['role' => UserRole::CentralAdmin]);

        $this->actingAs($user)
            ->get('/admin/central')
            ->assertOk()
            ->assertSee('Central Admin shell')
            ->assertSee('data-presentation-context="central-admin"', false);
    }

    public function test_central_route_names_are_unique_and_owned_by_the_boundary(): void
    {
        $names = collect(app('router')->getRoutes()->getRoutes())
            ->map(static fn (Route $route): ?string => $route->getName())
            ->filter(static fn (?string $name): bool => is_string($name) && str_starts_with($name, 'filament.central.'));

        self::assertNotEmpty($names);
        self::assertSame($names->count(), $names->unique()->count());
    }

    public function test_legacy_admin_root_redirects_to_the_central_boundary(): void
    {
        $this->get('/admin')->assertRedirect('/admin/central');
    }
}
