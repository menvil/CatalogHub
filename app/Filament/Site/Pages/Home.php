<?php

declare(strict_types=1);

namespace App\Filament\Site\Pages;

use App\Models\Site;
use Filament\Pages\Page;
use Filament\Panel;

final class Home extends Page
{
    protected static ?string $navigationLabel = 'Home';

    protected static ?string $title = 'Site Admin';

    protected string $view = 'filament.site.pages.home';

    public string $siteName = '';

    public function mount(): void
    {
        $site = request()->attributes->get('site_context');

        abort_unless($site instanceof Site, 403);

        $this->siteName = $site->name;
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
