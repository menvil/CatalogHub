@props([
    'beforeLabel' => 'Before',
    'beforeValue' => null,
    'afterLabel' => 'After',
    'afterValue' => null,
    'fieldLabel' => null,
    'variant' => 'inline',
])

@php
    $normalizeDiffValue = function ($value): string {
        if (is_null($value)) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
        }

        return (string) $value;
    };

    $beforeText = $normalizeDiffValue($beforeValue);
    $afterText = $normalizeDiffValue($afterValue);

    $state = match (true) {
        $beforeText === '' && $afterText !== '' => 'added',
        $beforeText !== '' && $afterText === '' => 'removed',
        $beforeText !== $afterText => 'changed',
        default => 'unchanged',
    };

    $stateClasses = [
        'added' => 'border-admin-success/30 bg-admin-success-soft text-admin-success',
        'removed' => 'border-admin-danger/30 bg-admin-danger-soft text-admin-danger',
        'changed' => 'border-admin-warning/30 bg-admin-warning-soft text-admin-warning',
        'unchanged' => 'border-admin-border bg-admin-surface-muted text-admin-muted',
    ];

    $isSideBySide = $variant === 'side-by-side';
    $isPreformatted = function (string $value): bool {
        $trimmed = trim($value);

        return str_contains($value, "\n") || str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[');
    };
@endphp

<section
    {{ $attributes->class('rounded-admin-card border border-admin-border bg-admin-surface p-admin-card') }}
    data-admin-diff-viewer="{{ $variant }}"
    data-admin-diff-state="{{ $state }}"
>
    <div class="flex flex-wrap items-center gap-admin-field">
        @if ($fieldLabel)
            <h3 class="text-sm font-semibold text-admin-text">{{ $fieldLabel }}</h3>
        @endif

        <span class="rounded-admin-badge border px-2 py-0.5 text-xs font-semibold uppercase tracking-wide {{ $stateClasses[$state] }}">
            {{ $state }}
        </span>
    </div>

    <div class="mt-4 grid gap-admin-field {{ $isSideBySide ? 'md:grid-cols-2' : '' }}">
        @foreach ([
            ['label' => $beforeLabel, 'value' => $beforeText, 'tone' => 'before'],
            ['label' => $afterLabel, 'value' => $afterText, 'tone' => 'after'],
        ] as $side)
            <div class="rounded-admin-input border border-admin-border bg-admin-surface-muted p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-admin-muted">{{ $side['label'] }}</p>

                @if ($side['value'] === '')
                    <p class="mt-2 text-sm italic text-admin-muted">Empty</p>
                @elseif ($isPreformatted($side['value']))
                    <pre class="mt-2 max-h-64 overflow-auto whitespace-pre-wrap break-words rounded-admin-input bg-admin-text p-3 text-xs text-white">{{ $side['value'] }}</pre>
                @else
                    <p class="mt-2 break-words text-sm text-admin-text">{{ $side['value'] }}</p>
                @endif
            </div>
        @endforeach
    </div>
</section>
