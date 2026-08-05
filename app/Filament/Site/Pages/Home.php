<?php

declare(strict_types=1);

namespace App\Filament\Site\Pages;

use App\Models\Site;
use App\Support\Sites\SiteRuntimeContext;
use Filament\Pages\Page;
use Filament\Panel;

final class Home extends Page
{
    protected static string $layout = 'layouts.site-admin';

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

    /** @return array<string, mixed> */
    protected function getLayoutData(): array
    {
        $site = request()->attributes->get('site_context');
        $runtimeContext = request()->attributes->get(SiteRuntimeContext::class);

        return [
            'activeNav' => 'dashboard',
            'pageTitle' => 'Site Admin',
            'siteAdminCurrentSite' => $site,
            'siteAdminRuntimeContext' => $runtimeContext,
            'siteLabel' => $this->siteName,
            'title' => 'Site Admin',
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
