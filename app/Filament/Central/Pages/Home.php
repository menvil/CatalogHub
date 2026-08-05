<?php

declare(strict_types=1);

namespace App\Filament\Central\Pages;

use Filament\Pages\Page;
use Filament\Panel;

final class Home extends Page
{
    protected static string $layout = 'layouts.central-admin';

    protected static ?string $navigationLabel = 'Home';

    protected static ?string $title = 'Central Admin';

    protected string $view = 'filament.central.pages.home';

    /** @return array<string, mixed> */
    protected function getLayoutData(): array
    {
        return [
            'activeNav' => 'dashboard',
            'pageTitle' => 'Central Admin',
            'title' => 'Central Admin',
        ];
    }

    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'home';
    }
}
