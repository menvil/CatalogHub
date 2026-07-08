<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Panel;

class CentralDashboard extends Page
{
    protected static ?string $navigationLabel = 'Central Dashboard';

    protected static ?string $title = 'Central Dashboard';

    protected string $view = 'filament.pages.central-dashboard';

    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'central-dashboard';
    }
}
