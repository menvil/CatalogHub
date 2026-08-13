@props([
    'id', 'name', 'label', 'options' => [], 'selected' => [], 'help' => null, 'error' => null,
    'required' => false, 'disabled' => false,
])
@php
    $selectedValues = array_map('strval', is_array($selected) ? $selected : []);
    $describedBy = collect([
        $attributes->get('aria-describedby'),
        filled($help) ? "{$id}-help" : null,
        filled($error) ? "{$id}-error" : null,
    ])->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')->implode(' ');
    $groupAttributes = $attributes->except('aria-describedby');
    $fieldName = str_ends_with($name, '[]') ? $name : $name.'[]';
@endphp
<fieldset
    {{ $groupAttributes->class('min-w-0 space-y-2') }}
    data-ui-checkbox-list="{{ $id }}"
    @if (filled($error)) aria-invalid="true" @endif
    @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
>
    <legend class="text-sm font-medium text-admin-text">{{ $label }} @if ($required)<span class="text-admin-danger" aria-hidden="true">*</span>@endif</legend>
    <div class="grid gap-2 sm:grid-cols-2">
        @foreach ($options as $value => $optionLabel)
            <label for="{{ $id }}-{{ $loop->index }}" @class(['flex min-h-10 items-center gap-2 rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm text-admin-text', 'cursor-pointer hover:bg-admin-surface-muted' => ! $disabled, 'cursor-not-allowed opacity-60' => $disabled])>
                <input
                    id="{{ $id }}-{{ $loop->index }}"
                    name="{{ $fieldName }}"
                    type="checkbox"
                    value="{{ $value }}"
                    @checked(in_array((string) $value, $selectedValues, true))
                    @disabled($disabled)
                    class="h-5 w-5 shrink-0 cursor-pointer rounded border-admin-border text-admin-primary focus:ring-admin-primary disabled:cursor-not-allowed"
                >
                <span>{{ $optionLabel }}</span>
            </label>
        @endforeach
    </div>
    @if (filled($help))<p id="{{ $id }}-help" class="text-xs text-admin-muted">{{ $help }}</p>@endif
    @if (filled($error))<p id="{{ $id }}-error" class="text-xs font-medium text-admin-danger" role="alert">{{ $error }}</p>@endif
</fieldset>
