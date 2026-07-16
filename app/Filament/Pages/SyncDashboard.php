<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProjectionConflictResource;
use App\Filament\Resources\ProjectionJobResource;
use App\Filament\Resources\StaleProductResource;
use App\Filament\Widgets\SyncStatusWidget;
use App\Models\SyncLog;
use App\Models\User;
use App\Services\Sync\SyncDashboardService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

final class SyncDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = 'Sync';

    protected static ?string $navigationLabel = 'Sync Dashboard';

    protected static ?string $title = 'Sync Dashboard';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.sync-dashboard';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCatalogHubPermission('central.manage');
    }

    /** @return array<class-string<SyncStatusWidget>> */
    protected function getHeaderWidgets(): array
    {
        return [SyncStatusWidget::class];
    }

    /** @return Collection<int, SyncLog> */
    public function recentLogs(): Collection
    {
        return app(SyncDashboardService::class)->recentLogs();
    }

    /** @return list<array{id: int, name: string, code: string, stale_products: int, last_sync_at: mixed}> */
    public function siteStatuses(): array
    {
        return app(SyncDashboardService::class)->siteStatuses();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('staleProducts')
                ->label('Stale products')
                ->icon(Heroicon::OutlinedExclamationCircle)
                ->url(StaleProductResource::getUrl()),
            Action::make('projectionJobs')
                ->label('Projection jobs')
                ->icon(Heroicon::OutlinedQueueList)
                ->url(ProjectionJobResource::getUrl()),
            Action::make('projectionConflicts')
                ->label('Projection conflicts')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->url(ProjectionConflictResource::getUrl()),
        ];
    }
}
