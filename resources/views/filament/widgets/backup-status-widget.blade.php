@php
    $status = $this->status();
@endphp

<x-filament-widgets::widget>
    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900" data-backup-status-widget>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Backup status</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Catalog snapshot and integrity visibility; snapshots do not replace infrastructure backups.</p>
            </div>
            <a class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400" href="{{ \App\Filament\Resources\CatalogSnapshotResource::getUrl() }}">Export history</a>
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
            @foreach ([
                ['label' => 'Last snapshot', 'value' => $status['last_snapshot_status']],
                ['label' => 'Snapshot age', 'value' => $status['last_snapshot_age_hours'] === null ? 'Never' : $status['last_snapshot_age_hours'].' h'],
                ['label' => 'Snapshot size', 'value' => \Illuminate\Support\Number::fileSize($status['last_snapshot_size'], precision: 0)],
                ['label' => 'Checksum status', 'value' => $status['last_checksum_verification_status']],
                ['label' => 'Missing media', 'value' => $status['missing_media_count']],
                ['label' => 'Failed exports', 'value' => $status['failed_exports_count']],
            ] as $metric)
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-950">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $metric['label'] }}</p>
                    <p class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">{{ $metric['value'] }}</p>
                </div>
            @endforeach
        </div>
    </section>
</x-filament-widgets::widget>
