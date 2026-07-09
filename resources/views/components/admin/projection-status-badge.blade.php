@props([
    'status' => 'missing',
    'label' => null,
    'lastUpdated' => null,
])

@php
    $statusClasses = [
        'synced' => 'bg-admin-success-soft text-admin-success ring-admin-success/20',
        'stale' => 'bg-admin-warning-soft text-admin-warning ring-admin-warning/25',
        'syncing' => 'bg-admin-info-soft text-admin-info ring-admin-info/25',
        'failed' => 'bg-admin-danger-soft text-admin-danger ring-admin-danger/25',
        'missing' => 'bg-admin-surface-muted text-admin-muted ring-admin-border',
    ];

    $statusLabels = [
        'synced' => 'Synced',
        'stale' => 'Stale',
        'syncing' => 'Syncing',
        'failed' => 'Failed',
        'missing' => 'Missing',
    ];

    $statusClass = $statusClasses[$status] ?? $statusClasses['missing'];
    $statusLabel = $label ?? ($statusLabels[$status] ?? $statusLabels['missing']);
@endphp

<span
    {{ $attributes->class([
        'inline-flex max-w-full items-center gap-1.5 rounded-admin-badge px-3 py-1 text-sm font-medium ring-1 ring-inset',
        $statusClass,
    ]) }}
    data-admin-projection-status="{{ $status }}"
>
    @if ($status === 'syncing')
        <span class="h-2 w-2 rounded-full bg-admin-info" aria-hidden="true"></span>
    @endif

    <span class="truncate">{{ $statusLabel }}</span>

    @if ($lastUpdated)
        <span class="text-xs opacity-80">{{ $lastUpdated }}</span>
    @endif
</span>
