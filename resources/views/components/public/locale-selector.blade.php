@props(['options' => []])

@if (count($options) === 1)
    <span class="text-foundation-label" data-public-locale-current>{{ $options[0]['code'] }}</span>
@elseif (count($options) > 1)
    <nav class="flex items-center gap-2 text-foundation-label" aria-label="Language" data-public-locale-selector>
        @foreach ($options as $option)
            <a
                href="{{ $option['url'] }}"
                hreflang="{{ $option['code'] }}"
                lang="{{ $option['code'] }}"
                @if ($option['current']) aria-current="page" @endif
                title="{{ $option['label'] }}"
                @class(['font-semibold underline' => $option['current']])
            >{{ $option['code'] }}</a>
        @endforeach
    </nav>
@endif
