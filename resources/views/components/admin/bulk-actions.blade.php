@props(['tableId', 'actions' => []])

<div {{ $attributes->class('flex flex-wrap items-center gap-admin-field') }} data-admin-bulk-actions="{{ $tableId }}" aria-live="polite">
    <span class="text-sm text-admin-muted"><span data-selected-count>0</span> selected</span>
    @foreach ($actions as $action)
        <button type="button" class="rounded-admin-input border border-admin-border px-3 py-2 text-sm font-semibold text-admin-text disabled:opacity-50" data-bulk-action="{{ $action['id'] }}" disabled>
            {{ $action['label'] }}
        </button>
    @endforeach
</div>
