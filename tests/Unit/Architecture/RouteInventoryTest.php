<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Filament\Facades\Filament;
use Illuminate\Routing\Route;
use Tests\TestCase;

final class RouteInventoryTest extends TestCase
{
    public function test_route_names_and_http_signatures_are_unique(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes());

        $names = $routes
            ->map(static fn (Route $route): ?string => $route->getName())
            ->filter()
            ->values();

        $signatures = $routes->map(static fn (Route $route): string => implode('|', [
            $route->getDomain() ?? '*',
            implode(',', $route->methods()),
            $route->uri(),
        ]));

        self::assertSame($names->count(), $names->unique()->count(), 'Duplicate route names detected.');
        self::assertSame($signatures->count(), $signatures->unique()->count(), 'Duplicate route method/domain/URI signatures detected.');
    }

    public function test_panel_ids_and_prefixes_are_unique(): void
    {
        $panels = collect(Filament::getPanels());
        $ids = $panels->keys();
        $prefixes = $panels->map(static fn ($panel): string => $panel->getPath());

        self::assertSame(['admin'], $ids->values()->all());
        self::assertSame($ids->count(), $ids->unique()->count(), 'Duplicate Filament panel IDs detected.');
        self::assertSame($prefixes->count(), $prefixes->unique()->count(), 'Duplicate Filament panel prefixes detected.');
    }

    public function test_current_home_and_login_entry_points_keep_their_http_outcomes(): void
    {
        $this->get('/')->assertOk();
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();
    }
}
