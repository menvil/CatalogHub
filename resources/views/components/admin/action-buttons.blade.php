@props([
    'actions' => [],
])

@if (count($actions) > 0)
    <div {{ $attributes->class('mt-4 flex flex-wrap gap-admin-field') }}>
        @foreach ($actions as $action)
            <button
                type="button"
                disabled
                class="rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm font-medium text-admin-muted"
            >
                {{ $action['label'] ?? $action }}
            </button>
        @endforeach
    </div>
@endif
