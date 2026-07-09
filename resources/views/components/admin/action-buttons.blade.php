@props([
    'actions' => [],
])

@if (count($actions) > 0)
    <div {{ $attributes->class('mt-4 flex flex-wrap gap-admin-field') }}>
        @foreach ($actions as $action)
            @php
                $label = is_array($action) ? ($action['label'] ?? $action['name'] ?? null) : $action;
                $disabled = is_array($action) ? (bool) ($action['disabled'] ?? false) : false;
            @endphp

            @if (! blank($label))
                <button
                    type="button"
                    @disabled($disabled)
                    class="rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm font-medium text-admin-muted"
                >
                    {{ $label }}
                </button>
            @endif
        @endforeach
    </div>
@endif
