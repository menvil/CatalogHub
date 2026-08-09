@props(['label', 'tone' => 'neutral', 'icon' => null, 'size' => 'md'])

@php
    $tones = ['success', 'warning', 'danger', 'info', 'neutral'];
    throw_unless(in_array($tone, $tones, true), \InvalidArgumentException::class, "Unknown status tone [{$tone}].");
@endphp

<x-admin.status-badge :label="$label" :variant="$tone" :icon="$icon" :size="$size" {{ $attributes }} data-ui-status="{{ $tone }}" />
