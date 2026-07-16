@php
    $diff = is_array($version->diff_json) ? $version->diff_json : [];
@endphp

<div class="space-y-5" data-product-version-preview="{{ $version->version }}">
    <dl class="grid gap-3 text-sm sm:grid-cols-2">
        <div>
            <dt class="font-medium text-gray-500 dark:text-gray-400">Change type</dt>
            <dd class="mt-1 text-gray-950 dark:text-white">{{ $version->change_type }}</dd>
        </div>
        <div>
            <dt class="font-medium text-gray-500 dark:text-gray-400">Changed by</dt>
            <dd class="mt-1 text-gray-950 dark:text-white">{{ $version->changedBy?->name ?? 'System' }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="font-medium text-gray-500 dark:text-gray-400">Reason</dt>
            <dd class="mt-1 text-gray-950 dark:text-white">{{ $version->reason ?: 'No reason provided.' }}</dd>
        </div>
    </dl>

    <section class="space-y-3">
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Diff</h3>
        @forelse ($diff as $field => $change)
            <x-admin.diff-viewer
                :field-label="(string) $field"
                :before-value="is_array($change) ? ($change['old'] ?? null) : null"
                :after-value="is_array($change) ? ($change['new'] ?? $change) : $change"
                variant="side-by-side"
            />
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">No field-level diff was recorded.</p>
        @endforelse
    </section>

    <details class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
        <summary class="cursor-pointer text-sm font-semibold text-gray-950 dark:text-white">Snapshot</summary>
        <pre class="mt-3 max-h-80 overflow-auto whitespace-pre-wrap break-words text-xs text-gray-700 dark:text-gray-200">{{ json_encode($version->snapshot_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
    </details>
</div>
