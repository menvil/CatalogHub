@props([
    'items' => [],
    'activeNav' => null,
])

<aside
    id="site-navigation"
    {{ $attributes->class('flex w-full flex-col border-b border-admin-border bg-admin-surface text-admin-text shadow-admin-card lg:min-h-screen lg:w-72 lg:border-b-0 lg:border-r') }}
    aria-label="Site Admin navigation"
>
    <div class="border-b border-admin-border px-admin-card py-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-admin-muted">CatalogHub</p>
        <p class="mt-1 text-lg font-semibold text-admin-text">Site Admin</p>
    </div>

    <nav class="flex gap-1 overflow-x-auto p-3 lg:flex-col lg:overflow-visible" aria-label="Site Admin sections">
        @foreach ($items as $item)
            @php
                $label = $item['label'] ?? '';
                $isActive = $activeNav === $label || $activeNav === ($item['id'] ?? null);
                $url = $item['url'] ?? null;
                $icon = $item['icon'] ?? null;
            @endphp

            @if ($url)
                <a
                    href="{{ $url }}"
                    @if ($isActive) aria-current="page" @endif
                    @class([
                        'whitespace-nowrap rounded-admin-input px-3 py-2 text-sm font-medium transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary',
                        'bg-admin-primary-soft text-admin-primary' => $isActive,
                        'text-admin-muted hover:bg-admin-surface-muted hover:text-admin-text' => ! $isActive,
                    ])
                >
                    <span class="flex items-center gap-2">
                        @if ($icon)
                            <x-ui.icon :name="$icon" size="sm" />
                        @endif
                        <span>{{ $label }}</span>
                    </span>
                </a>
            @else
                <span
                    @if ($isActive) aria-current="page" @endif
                    aria-disabled="true"
                    @class([
                        'whitespace-nowrap rounded-admin-input px-3 py-2 text-sm font-medium',
                        'bg-admin-primary-soft text-admin-primary' => $isActive,
                        'text-admin-muted' => ! $isActive,
                    ])
                >
                    <span class="flex items-center gap-2">
                        @if ($icon)
                            <x-ui.icon :name="$icon" size="sm" />
                        @endif
                        <span>{{ $label }}</span>
                    </span>
                </span>
            @endif
        @endforeach
    </nav>
</aside>
