@props(['id', 'name', 'label', 'value' => '1', 'checked' => false, 'help' => null, 'error' => null, 'disabled' => false])
@php($describedBy = collect([filled($help) ? "{$id}-help" : null, filled($error) ? "{$id}-error" : null])->filter()->implode(' '))
<div class="min-w-0 space-y-1.5" data-ui-form-field="{{ $id }}">
    <label for="{{ $id }}" @class(['inline-flex min-h-10 items-center gap-2 text-sm font-medium text-admin-text', 'cursor-pointer' => ! $disabled, 'cursor-not-allowed opacity-60' => $disabled])>
        <input id="{{ $id }}" name="{{ $name }}" type="checkbox" value="{{ $value }}" @checked($checked) @disabled($disabled)
            @if (filled($error)) aria-invalid="true" @endif @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->class('h-5 w-5 shrink-0 cursor-pointer rounded border-admin-border text-admin-primary focus:ring-admin-primary disabled:cursor-not-allowed') }}>
        <span>{{ $label }}</span>
    </label>
    @if (filled($help))<p id="{{ $id }}-help" class="text-xs text-admin-muted">{{ $help }}</p>@endif
    @if (filled($error))<p id="{{ $id }}-error" class="text-xs font-medium text-admin-danger" role="alert">{{ $error }}</p>@endif
</div>
