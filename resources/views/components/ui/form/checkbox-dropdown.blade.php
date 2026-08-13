@props([
    'id', 'name', 'label', 'options' => [], 'selected' => [], 'help' => null, 'error' => null,
    'required' => false, 'disabled' => false,
])
@php
    $selectedValues = array_map('strval', is_array($selected) ? $selected : []);
    $fieldName = str_ends_with($name, '[]') ? $name : $name.'[]';
    $describedBy = collect([filled($help) ? "{$id}-help" : null, filled($error) ? "{$id}-error" : null])->filter()->implode(' ');
@endphp
<div class="min-w-0 space-y-1.5" data-ui-checkbox-dropdown="{{ $id }}">
    <span class="block text-sm font-medium text-admin-text">{{ $label }} @if ($required)<span class="text-admin-danger" aria-hidden="true">*</span>@endif</span>
    <details class="group relative" @if ($disabled) inert @endif>
        <summary
            @class([
                'flex min-h-10 list-none items-center justify-between gap-3 rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm text-admin-text marker:hidden focus:border-admin-primary focus:outline-none focus:ring-2 focus:ring-admin-primary/20 [&::-webkit-details-marker]:hidden',
                'cursor-pointer hover:bg-admin-surface-muted' => ! $disabled,
                'cursor-not-allowed opacity-60' => $disabled,
            ])
        >
            <span><span data-ui-checkbox-dropdown-count>{{ count($selectedValues) }}</span> selected</span>
            <x-ui.icon name="chevron-down" decorative size="sm" class="transition-transform group-open:rotate-180" />
        </summary>
        <div class="absolute z-30 mt-1 max-h-52 w-full min-w-56 overflow-y-auto rounded-admin-input border border-admin-border bg-admin-surface p-1 shadow-admin-modal" @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif>
            @foreach ($options as $value => $optionLabel)
                <label for="{{ $id }}-{{ $loop->index }}" class="flex min-h-10 cursor-pointer items-center gap-2 rounded-admin-input px-3 py-2 text-sm text-admin-text hover:bg-admin-surface-muted">
                    <input id="{{ $id }}-{{ $loop->index }}" name="{{ $fieldName }}" type="checkbox" value="{{ $value }}" @checked(in_array((string) $value, $selectedValues, true)) @disabled($disabled) class="h-5 w-5 shrink-0 cursor-pointer rounded border-admin-border text-admin-primary focus:ring-admin-primary disabled:cursor-not-allowed">
                    <span>{{ $optionLabel }}</span>
                </label>
            @endforeach
        </div>
    </details>
    @if (filled($help))<p id="{{ $id }}-help" class="text-xs text-admin-muted">{{ $help }}</p>@endif
    @if (filled($error))<p id="{{ $id }}-error" class="text-xs font-medium text-admin-danger" role="alert">{{ $error }}</p>@endif
</div>
