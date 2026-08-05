<section
    {{ $attributes->class('flex flex-col gap-2 rounded-admin-card border border-admin-border bg-admin-surface p-admin-card sm:flex-row sm:items-center sm:justify-between') }}
    aria-label="Site synchronization status"
    data-site-sync-status="{{ $state }}"
>
    <div>
        <h2 class="text-sm font-semibold text-admin-text">Synchronization</h2>
        <p class="mt-1 text-sm text-admin-muted">{{ $status['description'] }}</p>
    </div>

    <span @class([
        'w-fit rounded-admin-badge px-2 py-1 text-xs font-semibold',
        'bg-admin-surface-muted text-admin-muted' => $status['tone'] === 'neutral',
        'bg-admin-warning-soft text-admin-warning' => $status['tone'] === 'warning',
    ])>
        {{ $status['label'] }}
    </span>
</section>
