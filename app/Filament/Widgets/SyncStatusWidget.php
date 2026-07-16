<?php

namespace App\Filament\Widgets;

use App\Services\Sync\SyncDashboardService;
use Filament\Widgets\Widget;

final class SyncStatusWidget extends Widget
{
    protected string $view = 'filament.widgets.sync-status-widget';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    /** @return array{stale_products: int, failed_projections: int, open_conflicts: int, sites: int} */
    public function summary(): array
    {
        return app(SyncDashboardService::class)->summary();
    }
}
