@props([
    'id', 'name', 'label', 'options' => [], 'selected' => [], 'help' => null, 'error' => null,
    'required' => false, 'disabled' => false,
])
@php
    $selectedValues = array_map('strval', is_array($selected) ? $selected : []);
    $fieldName = str_ends_with($name, '[]') ? $name : $name.'[]';
    $describedBy = collect([filled($help) ? "{$id}-help" : null, filled($error) ? "{$id}-error" : null])->filter()->implode(' ');
@endphp
<fieldset class="min-w-0 space-y-1.5" data-ui-scrollable-checkbox-list="{{ $id }}" @if (filled($error)) aria-invalid="true" @endif @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif>
    <legend class="text-sm font-medium text-admin-text">{{ $label }} @if ($required)<span class="text-admin-danger" aria-hidden="true">*</span>@endif</legend>
    <div class="max-h-40 overflow-y-auto rounded-admin-input border border-admin-border bg-admin-surface p-1" role="listbox" aria-multiselectable="true">
        @foreach ($options as $value => $optionLabel)
            <label for="{{ $id }}-{{ $loop->index }}" @class(['flex min-h-10 items-center gap-2 rounded-admin-input px-3 py-2 text-sm text-admin-text', 'cursor-pointer hover:bg-admin-surface-muted' => ! $disabled, 'cursor-not-allowed opacity-60' => $disabled]) role="option">
                <input id="{{ $id }}-{{ $loop->index }}" name="{{ $fieldName }}" type="checkbox" value="{{ $value }}" @checked(in_array((string) $value, $selectedValues, true)) @disabled($disabled) class="h-5 w-5 shrink-0 cursor-pointer rounded border-admin-border text-admin-primary focus:ring-admin-primary disabled:cursor-not-allowed">
                <span>{{ $optionLabel }}</span>
            </label>
        @endforeach
    </div>
    @if (filled($help))<p id="{{ $id }}-help" class="text-xs text-admin-muted">{{ $help }}</p>@endif
    @if (filled($error))<p id="{{ $id }}-error" class="text-xs font-medium text-admin-danger" role="alert">{{ $error }}</p>@endif
</fieldset>
