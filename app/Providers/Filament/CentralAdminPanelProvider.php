<?php

namespace App\Providers\Filament;

use App\Filament\Central\Pages\Home;
use App\Filament\Pages\CentralDashboard;
use App\Filament\Pages\CreateSiteWizard;
use App\Filament\Pages\ImportWizard;
use App\Filament\Pages\SnapshotGenerationPage;
use App\Filament\Pages\SyncDashboard;
use App\Filament\Pages\TranslationDashboard;
use App\Http\Middleware\EnsureCentralAdminAccess;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class CentralAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('central')
            ->path('admin/central')
            ->viteTheme('resources/css/central-admin.css')
            ->brandName('CatalogHub Central')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->pages([
                Home::class,
                CentralDashboard::class,
                CreateSiteWizard::class,
                ImportWizard::class,
                SnapshotGenerationPage::class,
                SyncDashboard::class,
                TranslationDashboard::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureCentralAdminAccess::class,
            ]);
    }
}
