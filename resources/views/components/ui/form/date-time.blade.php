@props(['id', 'name', 'label', 'value' => null, 'timezone', 'help' => null, 'error' => null, 'required' => false, 'disabled' => false, 'min' => null, 'max' => null])
@php
    $timezoneHelp = trim((string) $timezone) === '' ? null : 'Timezone: '.$timezone;
    $combinedHelp = collect([$help, $timezoneHelp])->filter()->implode(' · ');
@endphp
<x-ui.form.date-picker
    :id="$id"
    :name="$name"
    :label="$label"
    :value="$value"
    :help="$combinedHelp"
    :error="$error"
    :required="$required"
    :disabled="$disabled"
    :min="$min"
    :max="$max"
    :with-time="true"
    {{ $attributes }}
/>
