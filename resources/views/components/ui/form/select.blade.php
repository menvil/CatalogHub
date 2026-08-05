@props([
    'id', 'name', 'label', 'options' => [], 'selected' => null, 'placeholder' => null,
    'help' => null, 'error' => null, 'required' => false, 'disabled' => false,
])
@php($describedBy = collect([$help ? "{$id}-help" : null, $error ? "{$id}-error" : null])->filter()->implode(' '))
<x-ui.form.field :id="$id" :label="$label" :required="$required" :help="$help" :error="$error">
    <select id="{{ $id }}" name="{{ $name }}" @required($required) @disabled($disabled)
        @if ($error) aria-invalid="true" @endif @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class('block min-h-10 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm text-admin-text focus:border-admin-primary focus:outline-none focus:ring-2 focus:ring-admin-primary/20 disabled:opacity-60') }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $value => $option)
            @if (is_array($option))
                <optgroup label="{{ $value }}">
                    @foreach ($option as $groupValue => $groupLabel)<option value="{{ $groupValue }}" @selected((string) $selected === (string) $groupValue)>{{ $groupLabel }}</option>@endforeach
                </optgroup>
            @else
                <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $option }}</option>
            @endif
        @endforeach
    </select>
</x-ui.form.field>
