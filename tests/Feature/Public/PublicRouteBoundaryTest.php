<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Tests\TestCase;

final class PublicRouteBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_is_accessible_on_a_controlled_host_without_an_admin_guard(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);

        $this->get('http://tech-compare.test/en-US')
            ->assertOk()
            ->assertSee('data-presentation-context="public-site"', false);

        $route = app('router')->getRoutes()->getByName('public.home');
        self::assertInstanceOf(Route::class, $route);
        self::assertNotContains('auth', $route->gatherMiddleware());
        self::assertNotContains('auth:web', $route->gatherMiddleware());
    }

    public function test_application_owned_public_routes_use_the_public_name_prefix(): void
    {
        foreach (app('router')->getRoutes()->getRoutes() as $route) {
            if (! str_starts_with($route->getActionName(), 'App\\Http\\Controllers\\Public\\')) {
                continue;
            }

            self::assertNotNull($route->getName());
            self::assertStringStartsWith('public.', (string) $route->getName());
        }
    }

    public function test_public_routes_are_registered_from_their_owned_route_file(): void
    {
        self::assertFileExists(base_path('routes/public.php'));
        self::assertStringContainsString(
            "require __DIR__.'/public.php';",
            (string) file_get_contents(base_path('routes/web.php')),
        );
    }
}
