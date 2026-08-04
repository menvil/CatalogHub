<?php

declare(strict_types=1);

namespace App\Filament\Central\Pages;

use Filament\Pages\Page;
use Filament\Panel;

final class Home extends Page
{
    protected static ?string $navigationLabel = 'Home';

    protected static ?string $title = 'Central Admin';

    protected string $view = 'filament.central.pages.home';

    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'home';
    }
}
