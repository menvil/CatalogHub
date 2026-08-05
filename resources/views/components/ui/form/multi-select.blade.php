@props([
    'id', 'name', 'label', 'options' => [], 'selected' => [], 'help' => null, 'error' => null,
    'required' => false, 'disabled' => false, 'size' => 5,
])
@php
    $selectedValues = array_map('strval', is_array($selected) ? $selected : []);
    $describedBy = collect([filled($help) ? "{$id}-help" : null, filled($error) ? "{$id}-error" : null])->filter()->implode(' ');
@endphp
<x-ui.form.field :id="$id" :label="$label" :required="$required" :help="$help" :error="$error">
    <select id="{{ $id }}" name="{{ str_ends_with($name, '[]') ? $name : $name.'[]' }}" multiple size="{{ $size }}" @required($required) @disabled($disabled)
        @if (filled($error)) aria-invalid="true" @endif @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class('block w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm text-admin-text focus:border-admin-primary focus:outline-none focus:ring-2 focus:ring-admin-primary/20 disabled:opacity-60') }}>
        @foreach ($options as $value => $option)
            @if (is_array($option))
                <optgroup label="{{ $value }}">@foreach ($option as $groupValue => $groupLabel)<option value="{{ $groupValue }}" @selected(in_array((string) $groupValue, $selectedValues, true))>{{ $groupLabel }}</option>@endforeach</optgroup>
            @else
                <option value="{{ $value }}" @selected(in_array((string) $value, $selectedValues, true))>{{ $option }}</option>
            @endif
        @endforeach
    </select>
</x-ui.form.field>
