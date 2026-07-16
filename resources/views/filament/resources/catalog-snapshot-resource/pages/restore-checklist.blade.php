@php
    $snapshot = $this->snapshot();
    $files = $snapshot->files_json ?? [];
@endphp

<x-filament-panels::page>
    <div class="space-y-6" data-restore-checklist="{{ $snapshot->uuid }}">
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Selected snapshot</h2>
            <dl class="mt-4 grid gap-4 text-sm md:grid-cols-3">
                <div><dt class="text-gray-500">UUID</dt><dd class="mt-1 font-mono text-gray-950 dark:text-white">{{ $snapshot->uuid }}</dd></div>
                <div><dt class="text-gray-500">Status</dt><dd class="mt-1 text-gray-950 dark:text-white">{{ $snapshot->status }}</dd></div>
                <div><dt class="text-gray-500">Files</dt><dd class="mt-1 text-gray-950 dark:text-white">{{ count($files) }}</dd></div>
            </dl>

            <ul class="mt-4 space-y-1 text-sm text-gray-600 dark:text-gray-300">
                @forelse ($files as $fileKey => $file)
                    <li><span class="font-medium">{{ $fileKey }}</span>: <span class="font-mono">{{ $file['path'] ?? 'Missing path metadata' }}</span></li>
                @empty
                    <li>No snapshot files were recorded.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-xl border border-warning-200 bg-warning-50 p-5 dark:border-warning-800 dark:bg-warning-950">
            <h2 class="font-semibold text-warning-900 dark:text-warning-100">Manual restore only</h2>
            <p class="mt-2 text-sm text-warning-800 dark:text-warning-200">
                This checklist provides reviewed guidance. CatalogHub does not perform an automatic production restore.
            </p>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Restore Checklist</h2>
            <ol class="mt-4 space-y-3 text-sm text-gray-700 dark:text-gray-200">
                <li>1. Confirm an independent Database backup exists and is restorable.</li>
                <li>2. Confirm every snapshot file listed above exists on the private disk.</li>
                <li>3. Confirm the media manifest exists and the object-storage backup is available.</li>
                <li>4. Run checksum verification for the selected snapshot and media.</li>
                <li>5. Verify the target environment, application version, and schema compatibility.</li>
                <li>6. Disable writes and background workers during the reviewed manual restore.</li>
                <li>7. Import data only with approved, separately reviewed restore tooling.</li>
                <li>8. Rebuild projections, search documents, facets, and sitemaps.</li>
                <li>9. Run media integrity checks and critical smoke tests.</li>
                <li>10. Re-enable writes only after sign-off and record the restore outcome.</li>
            </ol>
        </section>
    </div>
</x-filament-panels::page>
