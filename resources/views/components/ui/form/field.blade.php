@props([
    'id',
    'label',
    'required' => false,
    'optional' => false,
    'help' => null,
    'error' => null,
])

@php(throw_if(trim((string) $id) === '', \InvalidArgumentException::class, 'Form field IDs must be explicit and non-empty.'))

<div {{ $attributes->class('space-y-1.5') }} data-ui-form-field="{{ $id }}">
    <x-ui.form.label :for="$id" :required="$required" :optional="$optional">{{ $label }}</x-ui.form.label>
    {{ $slot }}
    @if (filled($help))<p id="{{ $id }}-help" class="text-xs text-admin-muted">{{ $help }}</p>@endif
    @if (filled($error))<p id="{{ $id }}-error" class="text-xs font-medium text-admin-danger" role="alert">{{ $error }}</p>@endif
</div>
