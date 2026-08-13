@props(['id', 'name', 'label', 'value' => '1', 'checked' => false, 'help' => null, 'error' => null, 'disabled' => false])
@php($describedBy = collect([filled($help) ? "{$id}-help" : null, filled($error) ? "{$id}-error" : null])->filter()->implode(' '))
<div class="min-w-0 space-y-1.5" data-ui-form-field="{{ $id }}">
    <label for="{{ $id }}" data-ui-toggle-hit-area @class(['inline-flex min-h-11 w-full items-center justify-between gap-4 rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm font-medium text-admin-text', 'cursor-pointer hover:bg-admin-surface-muted' => ! $disabled, 'cursor-not-allowed opacity-60' => $disabled])>
        <span>{{ $label }}</span>
        <input id="{{ $id }}" name="{{ $name }}" type="checkbox" role="switch" value="{{ $value }}" @checked($checked) @disabled($disabled)
            @if (filled($error)) aria-invalid="true" @endif @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->class('peer sr-only') }}>
        <span aria-hidden="true" data-ui-toggle-indicator
            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full border border-admin-border bg-admin-surface-muted after:absolute after:left-0.5 after:top-1/2 after:h-5 after:w-5 after:-translate-y-1/2 after:rounded-full after:bg-admin-muted after:shadow-sm after:content-[''] after:transition-transform peer-checked:border-admin-primary peer-checked:bg-admin-primary peer-checked:after:translate-x-5 peer-checked:after:bg-white peer-focus-visible:ring-2 peer-focus-visible:ring-admin-primary/30 peer-focus-visible:ring-offset-2"></span>
    </label>
    @if (filled($help))<p id="{{ $id }}-help" class="text-xs text-admin-muted">{{ $help }}</p>@endif
    @if (filled($error))<p id="{{ $id }}-error" class="text-xs font-medium text-admin-danger" role="alert">{{ $error }}</p>@endif
</div>
