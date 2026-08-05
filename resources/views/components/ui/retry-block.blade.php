@props(['message', 'retryLabel' => 'Retry'])

<div {{ $attributes }} data-ui-retry-block>
    <x-ui.alert tone="danger" :message="$message" />
    <div class="mt-2">
        <x-ui.button type="button" variant="secondary" data-ui-retry>{{ $retryLabel }}</x-ui.button>
    </div>
</div>
