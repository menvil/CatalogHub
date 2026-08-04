<?php

namespace App\Services\Backup;

use App\Models\CatalogSnapshot;
use App\Models\MediaManifest;
use App\Models\SyncLog;

final class BackupStatusWidgetData
{
    /**
     * @return array{
     *     last_snapshot_status: string,
     *     last_snapshot_at: string|null,
     *     last_snapshot_age_hours: int|null,
     *     last_snapshot_size: int,
     *     last_checksum_verification_status: string,
     *     missing_media_count: int,
     *     failed_exports_count: int
     * }
     */
    public function resolve(): array
    {
        $snapshot = CatalogSnapshot::query()->latest()->first();
        $snapshotAt = $snapshot === null
            ? null
            : ($snapshot->completed_at ?? $snapshot->failed_at ?? $snapshot->created_at);
        $checksumLog = SyncLog::query()
            ->where('operation', 'verify_snapshot_checksums')
            ->latest()
            ->first();

        return [
            'last_snapshot_status' => $snapshot === null ? 'not_generated' : $snapshot->status,
            'last_snapshot_at' => $snapshotAt?->toISOString(),
            'last_snapshot_age_hours' => $snapshotAt === null
                ? null
                : (int) floor($snapshotAt->diffInHours(now())),
            'last_snapshot_size' => (int) collect($snapshot === null ? [] : ($snapshot->files_json ?? []))->sum(
                fn (array $file): int => (int) ($file['file_size'] ?? 0),
            ),
            'last_checksum_verification_status' => $checksumLog === null ? 'not_run' : $checksumLog->status,
            'missing_media_count' => MediaManifest::query()
                ->whereNull('catalog_snapshot_id')
                ->where('status', 'missing')
                ->count(),
            'failed_exports_count' => CatalogSnapshot::query()->where('status', 'failed')->count(),
        ];
    }
}
