@props([
    'actions' => [],
])

@if (count($actions) > 0)
    <div {{ $attributes->class('mt-4 flex flex-wrap gap-admin-field') }}>
        @foreach ($actions as $action)
            @php
                $label = is_array($action) ? ($action['label'] ?? $action['name'] ?? null) : $action;
                $href = is_array($action) ? ($action['href'] ?? null) : null;
            @endphp

            @if (! blank($label))
                @if ($href)
                    <a
                        href="{{ $href }}"
                        class="rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm font-medium text-admin-muted"
                    >
                        {{ $label }}
                    </a>
                @else
                    <button
                        type="button"
                        disabled
                        class="rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm font-medium text-admin-muted"
                    >
                        {{ $label }}
                    </button>
                @endif
            @endif
        @endforeach
    </div>
@endif
