@props(['id', 'name', 'label', 'value' => null, 'timezone', 'help' => null, 'error' => null, 'required' => false, 'disabled' => false])
@php
    $timezoneHelp = trim((string) $timezone) === '' ? null : 'Timezone: '.$timezone;
    $combinedHelp = collect([$help, $timezoneHelp])->filter()->implode(' · ');
@endphp
<x-ui.form.input :id="$id" :name="$name" :label="$label" type="datetime-local" :value="$value" :help="$combinedHelp" :error="$error" :required="$required" :disabled="$disabled" {{ $attributes }} />
