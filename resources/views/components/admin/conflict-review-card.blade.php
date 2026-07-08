@props([
    'title',
    'entityLabel',
    'fieldLabel',
    'sourceA',
    'sourceB',
    'valueA',
    'valueB',
    'severity' => 'medium',
    'actions' => [],
])

@php
    $severityVariant = match ($severity) {
        'critical', 'high' => 'danger',
        'medium' => 'warning',
        default => 'info',
    };
@endphp

<x-admin.card :title="$title" :description="$entityLabel" data-admin-conflict-review-card>
    <x-slot:actions>
        <x-admin.status-badge :label="$severity" :variant="$severityVariant" />
    </x-slot:actions>

    <x-admin.diff-viewer
        :field-label="$fieldLabel"
        :before-label="$sourceA"
        :before-value="$valueA"
        :after-label="$sourceB"
        :after-value="$valueB"
        variant="side-by-side"
    />

    @if (count($actions) > 0)
        <div class="mt-4 flex flex-wrap gap-admin-field">
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
</x-admin.card>
