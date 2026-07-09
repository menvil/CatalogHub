@props([
    'status' => 'missing',
    'locale' => null,
    'label' => null,
])

@php
    $statusClasses = [
        'missing' => 'bg-admin-danger-soft text-admin-danger ring-admin-danger/25',
        'machine' => 'bg-admin-info-soft text-admin-info ring-admin-info/25',
        'reviewed' => 'bg-admin-warning-soft text-admin-warning ring-admin-warning/25',
        'approved' => 'bg-admin-success-soft text-admin-success ring-admin-success/20',
        'outdated' => 'bg-admin-outdated-soft text-admin-outdated ring-admin-outdated/25',
    ];

    $statusLabels = [
        'missing' => 'Missing',
        'machine' => 'Machine',
        'reviewed' => 'Reviewed',
        'approved' => 'Approved',
        'outdated' => 'Outdated',
    ];

    $statusClass = $statusClasses[$status] ?? $statusClasses['missing'];
    $statusLabel = $label ?? ($statusLabels[$status] ?? $statusLabels['missing']);
@endphp

<span
    {{ $attributes->class([
        'inline-flex max-w-full items-center gap-1.5 rounded-admin-badge px-3 py-1 text-sm font-medium ring-1 ring-inset',
        $statusClass,
    ]) }}
    data-admin-translation-status="{{ $status }}"
>
    @if ($locale)
        <span class="text-xs font-semibold uppercase tracking-wide">{{ $locale }}</span>
    @endif

    <span class="truncate">{{ $statusLabel }}</span>
</span>
