@props([
    'requestTitle',
    'requesterLabel',
    'sourceSiteLabel' => null,
    'entityLabel',
    'fieldLabel',
    'currentValue',
    'proposedValue',
    'status' => 'pending',
    'submittedAt' => null,
    'actions' => [],
])

@php
    $statusVariant = match ($status) {
        'approved', 'merged' => 'success',
        'rejected' => 'danger',
        'needs_info' => 'warning',
        default => 'info',
    };
    $statusLabel = \Illuminate\Support\Str::of($status)->replace('_', ' ')->title()->toString();
@endphp

<x-admin.card :title="$requestTitle" :description="$entityLabel" data-admin-change-request-card>
    <x-slot:actions>
        <x-admin.status-badge :label="$statusLabel" :variant="$statusVariant" />
    </x-slot:actions>

    <div class="mb-4 grid gap-admin-field md:grid-cols-3">
        <div class="rounded-admin-input bg-admin-surface-muted p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-admin-muted">Requester</p>
            <p class="mt-1 text-sm font-medium text-admin-text">{{ $requesterLabel }}</p>
        </div>

        <div class="rounded-admin-input bg-admin-surface-muted p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-admin-muted">Source site</p>
            <p class="mt-1 text-sm font-medium text-admin-text">{{ $sourceSiteLabel ?: 'Central Admin' }}</p>
        </div>

        <div class="rounded-admin-input bg-admin-surface-muted p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-admin-muted">Submitted</p>
            <p class="mt-1 text-sm font-medium text-admin-text">{{ $submittedAt ?: 'Not submitted' }}</p>
        </div>
    </div>

    <x-admin.diff-viewer
        :field-label="$fieldLabel"
        before-label="Current"
        :before-value="$currentValue"
        after-label="Proposed"
        :after-value="$proposedValue"
        variant="side-by-side"
    />

    <x-admin.action-buttons :actions="$actions" />
</x-admin.card>
