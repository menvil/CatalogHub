<?php

namespace App\Services\Sync;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\Site;
use App\Models\SiteProductProjection;
use App\Models\SyncConflict;
use App\Models\SyncLog;
use Illuminate\Database\Eloquent\Collection;

final class SyncDashboardService
{
    public function __construct(private readonly StaleProductDetector $staleProductDetector) {}

    /** @return array{stale_products: int, failed_projections: int, open_conflicts: int, sites: int} */
    public function summary(): array
    {
        return [
            'stale_products' => $this->staleProductDetector->staleAcrossSites()->count(),
            'failed_projections' => SiteProductProjection::query()
                ->where(function ($query): void {
                    $query->where('status', ProjectionStatus::Failed)
                        ->orWhereNotNull('failed_at');
                })
                ->count(),
            'open_conflicts' => SyncConflict::query()->open()->count(),
            'sites' => Site::query()->count(),
        ];
    }

    /** @return Collection<int, SyncLog> */
    public function recentLogs(int $limit = 8): Collection
    {
        return SyncLog::query()
            ->with(['site', 'centralProduct'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /** @return list<array{id: int, name: string, code: string, stale_products: int, last_sync_at: mixed}> */
    public function siteStatuses(): array
    {
        return Site::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Site $site): array => [
                'id' => (int) $site->getKey(),
                'name' => (string) $site->name,
                'code' => (string) $site->code,
                'stale_products' => $this->staleProductDetector->staleForSite($site)->count(),
                'last_sync_at' => SyncLog::query()
                    ->where('site_id', $site->getKey())
                    ->where('status', 'completed')
                    ->max('finished_at'),
            ])
            ->all();
    }
}
