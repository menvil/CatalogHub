@props([
    'siteLabel' => 'Demo site',
    'marketLabel' => 'Default market',
    'localeLabel' => 'en',
])

<section
    {{ $attributes->class('rounded-admin-card border border-admin-border bg-admin-surface p-admin-card shadow-admin-card') }}
    aria-label="Site context"
>
    <div class="flex flex-col gap-admin-field sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-admin-muted">Current site</p>
            <p class="mt-1 text-base font-semibold text-admin-text">{{ $siteLabel }}</p>
        </div>

        <div class="flex flex-wrap gap-admin-field text-sm">
            <span class="rounded-admin-badge bg-admin-primary-soft px-3 py-1 font-medium text-admin-primary">
                {{ $marketLabel }}
            </span>
            <span class="rounded-admin-badge bg-admin-info-soft px-3 py-1 font-medium text-admin-info">
                Locale: {{ $localeLabel }}
            </span>
        </div>
    </div>
</section>
