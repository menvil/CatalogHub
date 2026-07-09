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

    <x-admin.action-buttons :actions="$actions" />
</x-admin.card>
