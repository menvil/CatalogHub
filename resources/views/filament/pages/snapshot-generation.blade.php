<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-xl border border-warning-200 bg-warning-50 p-5 text-sm text-warning-900 dark:border-warning-800 dark:bg-warning-950 dark:text-warning-100">
            <h2 class="font-semibold">Portable catalog export</h2>
            <p class="mt-2">
                A catalog snapshot is not a full database backup. Keep independent database and object-storage backups for disaster recovery.
            </p>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Included JSONL sections</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                Products, categories, brands, attribute schema and values, translations, media manifest, and safe site configuration can be generated independently.
            </p>
        </section>

        @if ($snapshot = $this->generatedSnapshot())
            <section class="rounded-xl border border-success-200 bg-success-50 p-5 dark:border-success-800 dark:bg-success-950" data-generated-snapshot="{{ $snapshot->uuid }}">
                <h2 class="font-semibold text-success-900 dark:text-success-100">Latest generated snapshot</h2>
                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
                    <div><dt class="text-success-700 dark:text-success-300">UUID</dt><dd class="font-mono">{{ $snapshot->uuid }}</dd></div>
                    <div><dt class="text-success-700 dark:text-success-300">Status</dt><dd>{{ $snapshot->status }}</dd></div>
                    <div><dt class="text-success-700 dark:text-success-300">Files</dt><dd>{{ count($snapshot->files_json ?? []) }}</dd></div>
                </dl>
            </section>
        @endif
    </div>
</x-filament-panels::page>
