@props([
    'steps' => [],
    'currentStep' => null,
    'orientation' => 'horizontal',
])

@php
    $statusClasses = [
        'pending' => [
            'marker' => 'bg-admin-surface text-admin-muted ring-admin-border',
            'label' => 'text-admin-muted',
        ],
        'current' => [
            'marker' => 'bg-admin-primary text-white ring-admin-primary',
            'label' => 'text-admin-primary',
        ],
        'completed' => [
            'marker' => 'bg-admin-success text-white ring-admin-success',
            'label' => 'text-admin-success',
        ],
        'error' => [
            'marker' => 'bg-admin-danger text-white ring-admin-danger',
            'label' => 'text-admin-danger',
        ],
    ];

    $isVertical = $orientation === 'vertical';
@endphp

<ol
    {{ $attributes->class([
        'grid gap-admin-field',
        'md:grid-cols-[repeat(var(--step-count),minmax(0,1fr))]' => ! $isVertical,
        'grid-cols-1' => $isVertical,
    ]) }}
    style="--step-count: {{ max(count($steps), 1) }}"
    data-admin-stepper="{{ $orientation }}"
>
    @foreach ($steps as $index => $step)
        @php
            $key = $step['key'] ?? (string) $index;
            $label = $step['label'] ?? $key;
            $status = $step['status'] ?? ($currentStep === $key ? 'current' : 'pending');
            $classes = $statusClasses[$status] ?? $statusClasses['pending'];
        @endphp

        <li class="flex gap-admin-field {{ $isVertical ? '' : 'md:flex-col' }}" data-admin-step-status="{{ $status }}">
            <div class="flex items-center gap-admin-field">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-admin-badge text-sm font-semibold ring-2 ring-inset {{ $classes['marker'] }}">
                    @if ($status === 'completed')
                        ✓
                    @elseif ($status === 'error')
                        !
                    @else
                        {{ $index + 1 }}
                    @endif
                </span>

                @if (! $loop->last)
                    <span class="hidden h-px flex-1 bg-admin-border md:block" aria-hidden="true"></span>
                @endif
            </div>

            <div class="min-w-0">
                <p class="text-sm font-semibold {{ $classes['label'] }}">{{ $label }}</p>

                @if (! empty($step['description']))
                    <p class="mt-1 text-xs text-admin-muted">{{ $step['description'] }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
