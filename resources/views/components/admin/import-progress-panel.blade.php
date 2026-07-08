@props([
    'sourceName',
    'categoryName' => null,
    'status' => 'pending',
    'steps' => [],
    'stats' => [],
])

<section
    {{ $attributes->class('rounded-admin-card border border-admin-border bg-admin-surface p-admin-card shadow-admin-card') }}
    data-admin-import-progress-panel
>
    <div class="flex flex-col gap-admin-field md:flex-row md:items-start md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-admin-muted">Import progress</p>
            <h2 class="mt-1 text-lg font-semibold text-admin-text">{{ $sourceName }}</h2>

            @if ($categoryName)
                <p class="mt-1 text-sm text-admin-muted">{{ $categoryName }}</p>
            @endif
        </div>

        <x-admin.status-badge :label="$status" variant="{{ $status === 'failed' ? 'danger' : ($status === 'completed' ? 'success' : 'info') }}" />
    </div>

    <div class="mt-5">
        <x-admin.stepper-wizard :steps="$steps" />
    </div>

    <div class="mt-5 grid gap-admin-field sm:grid-cols-2 lg:grid-cols-5">
        @foreach ($stats as $stat)
            <div class="rounded-admin-input border border-admin-border bg-admin-surface-muted p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-admin-muted">{{ $stat['label'] ?? 'Stat' }}</p>
                <p class="mt-2 text-2xl font-semibold text-admin-text">{{ $stat['value'] ?? 0 }}</p>
            </div>
        @endforeach
    </div>
</section>
