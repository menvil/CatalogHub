@props([
    'level' => 'low',
    'label',
    'count' => null,
])

@php
    $levelClasses = [
        'low' => 'bg-admin-info-soft text-admin-info ring-admin-info/25',
        'medium' => 'bg-admin-warning-soft text-admin-warning ring-admin-warning/25',
        'high' => 'bg-orange-100 text-orange-800 ring-orange-300',
        'critical' => 'bg-admin-danger-soft text-admin-danger ring-admin-danger/30',
    ];

    $levelLabels = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ];

    $levelClass = $levelClasses[$level] ?? $levelClasses['low'];
    $levelLabel = $levelLabels[$level] ?? $levelLabels['low'];
@endphp

<span
    {{ $attributes->class([
        'inline-flex max-w-full items-center gap-2 rounded-admin-badge px-3 py-1 text-sm font-medium ring-1 ring-inset',
        $levelClass,
    ]) }}
    data-admin-quality-warning="{{ $level }}"
>
    <span class="text-xs font-semibold uppercase tracking-wide">{{ $levelLabel }}</span>
    <span class="truncate">{{ $label }}</span>

    @if (! is_null($count))
        <span class="rounded-admin-badge bg-admin-surface px-2 py-0.5 text-xs font-semibold text-admin-text ring-1 ring-inset ring-admin-border">
            {{ $count }}
        </span>
    @endif
</span>
