@props(['id', 'name', 'label', 'options' => [], 'selected' => null, 'help' => null, 'error' => null, 'disabled' => false])
<fieldset {{ $attributes->class('space-y-2') }} @if (filled($error)) aria-invalid="true" @endif @if (filled($help) || filled($error)) aria-describedby="{{ collect([filled($help) ? $id.'-help' : null, filled($error) ? $id.'-error' : null])->filter()->implode(' ') }}" @endif>
    <legend class="text-sm font-medium text-admin-text">{{ $label }}</legend>
    @foreach ($options as $value => $optionLabel)
        <label class="flex items-center gap-2 text-sm text-admin-text" for="{{ $id }}-{{ $loop->index }}">
            <input id="{{ $id }}-{{ $loop->index }}" name="{{ $name }}" type="radio" value="{{ $value }}" @checked((string) $selected === (string) $value) @disabled($disabled) class="border-admin-border text-admin-primary focus:ring-admin-primary">
            <span>{{ $optionLabel }}</span>
        </label>
    @endforeach
    @if (filled($help))<p id="{{ $id }}-help" class="text-xs text-admin-muted">{{ $help }}</p>@endif
    @if (filled($error))<p id="{{ $id }}-error" class="text-xs font-medium text-admin-danger" role="alert">{{ $error }}</p>@endif
</fieldset>
