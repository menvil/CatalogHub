@props([
    'context' => 'central',
    'items' => [],
    'activeNav' => null,
])

@php
    $contextLabel = $context === 'site' ? 'Site Admin' : 'Central Admin';
@endphp

<aside
    {{ $attributes->class([
        'flex w-full flex-col border-admin-border bg-admin-surface text-admin-text shadow-admin-card',
        'border-b lg:min-h-screen lg:w-72 lg:border-b-0 lg:border-r',
    ]) }}
    aria-label="{{ $contextLabel }} navigation"
>
    <div class="border-b border-admin-border px-admin-card py-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-admin-muted">CatalogHub</p>
        <p class="mt-1 text-lg font-semibold text-admin-text">{{ $contextLabel }}</p>
    </div>

    <nav class="flex gap-1 overflow-x-auto p-3 lg:flex-col lg:overflow-visible" aria-label="{{ $contextLabel }} sections">
        @foreach ($items as $item)
            @php
                $label = $item['label'] ?? '';
                $isActive = $activeNav === $label || $activeNav === ($item['key'] ?? null);
                $url = $item['url'] ?? null;
            @endphp

            @if ($url)
                <a
                    href="{{ $url }}"
                    @if ($isActive) aria-current="page" @endif
                    @class([
                        'whitespace-nowrap rounded-admin-input px-3 py-2 text-sm font-medium transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary',
                        'bg-admin-primary-soft text-admin-primary' => $isActive,
                        'text-admin-muted hover:bg-admin-surface-muted hover:text-admin-text' => ! $isActive,
                    ])"
                >
                    {{ $label }}
                </a>
            @else
                <span
                    @if ($isActive) aria-current="page" @endif
                    aria-disabled="true"
                    @class([
                        'whitespace-nowrap rounded-admin-input px-3 py-2 text-sm font-medium',
                        'bg-admin-primary-soft text-admin-primary' => $isActive,
                        'text-admin-muted' => ! $isActive,
                    ])"
                >
                    {{ $label }}
                </span>
            @endif
        @endforeach
    </nav>
</aside>
