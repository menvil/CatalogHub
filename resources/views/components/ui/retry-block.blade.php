@props(['message', 'retryLabel' => 'Retry'])

<x-ui.alert tone="danger" :message="$message" {{ $attributes }}>
</x-ui.alert>
<div class="mt-2">
    <x-ui.button type="button" variant="secondary" data-ui-retry>{{ $retryLabel }}</x-ui.button>
</div>
