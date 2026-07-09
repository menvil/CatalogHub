@props([
    'items' => [],
    'active' => null,
])

<nav
    {{ $attributes->class('border-b border-admin-border') }}
    aria-label="Admin tabs"
    role="tablist"
>
    <div class="flex gap-1 overflow-x-auto">
        @foreach ($items as $item)
            @php
                $key = $item['key'] ?? $item['label'] ?? '';
                $label = $item['label'] ?? $key;
                $isActive = $active === $key;
                $rawUrl = (string) ($item['url'] ?? '#');
                $scheme = parse_url($rawUrl, PHP_URL_SCHEME);
                $url = match (true) {
                    $rawUrl === '#',
                    \Illuminate\Support\Str::startsWith($rawUrl, '#'),
                    \Illuminate\Support\Str::startsWith($rawUrl, '/') && ! \Illuminate\Support\Str::startsWith($rawUrl, '//'),
                    in_array($scheme, ['http', 'https'], true) => $rawUrl,
                    default => '#',
                };
            @endphp

            <a
                href="{{ $url }}"
                role="tab"
                tabindex="{{ $isActive ? '0' : '-1' }}"
                @if (! empty($item['panelId'])) aria-controls="{{ $item['panelId'] }}" @endif
                @if ($isActive) aria-selected="true" aria-current="page" @else aria-selected="false" @endif
                @class([
                    'inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary',
                    'border-admin-primary text-admin-primary' => $isActive,
                    'border-transparent text-admin-muted hover:border-admin-border hover:text-admin-text' => ! $isActive,
                ])
            >
                <span>{{ $label }}</span>

                @if (array_key_exists('count', $item) && ! is_null($item['count']))
                    <span @class([
                        'rounded-admin-badge px-2 py-0.5 text-xs font-semibold',
                        'bg-admin-primary-soft text-admin-primary' => $isActive,
                        'bg-admin-surface-muted text-admin-muted' => ! $isActive,
                    ])>
                        {{ $item['count'] }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>
</nav>
