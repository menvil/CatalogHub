<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Models\Site;
use App\Services\Sites\SiteDashboardMetrics;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

final class SiteDashboard extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SiteResource::class;

    protected string $view = 'filament.resources.site-resource.pages.site-dashboard';

    protected static ?string $title = 'Site Dashboard';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    /** @return array<string, int|string> */
    public function getMetrics(): array
    { /** @var Site $site */ $site = $this->getRecord();

        return app(SiteDashboardMetrics::class)->for($site);
    }
}
