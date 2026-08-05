@props([
    'items' => [],
    'activeNav' => null,
])

<button
    type="button"
    class="central-shell-sidebar-backdrop"
    aria-label="Close navigation"
    data-central-sidebar-backdrop
></button>

<aside
    id="central-navigation"
    {{ $attributes->class([
        'central-shell-sidebar flex flex-col border-admin-border bg-admin-surface text-admin-text shadow-admin-card',
    ]) }}
    aria-label="Central Admin navigation"
    data-central-sidebar-mobile-open="false"
    data-central-sidebar-preference="local"
>
    <div class="flex min-h-16 items-center justify-between gap-2 border-b border-admin-border px-admin-card py-3">
        <div class="central-sidebar-label min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-admin-muted">CatalogHub</p>
            <p class="mt-1 truncate text-lg font-semibold text-admin-text">Central Admin</p>
        </div>
        <span class="central-sidebar-collapsed-mark text-sm font-semibold text-admin-text" aria-hidden="true">CH</span>

        <button
            type="button"
            class="central-sidebar-mobile-close rounded-admin-input p-2 text-admin-muted hover:bg-admin-surface-muted hover:text-admin-text focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary"
            aria-label="Close navigation"
            data-central-sidebar-close
        >
            <x-ui.icon name="x-mark" size="sm" />
        </button>
    </div>

    <nav class="flex flex-1 flex-col gap-1 overflow-y-auto p-3" aria-label="Central Admin sections">
        @foreach ($items as $item)
            @php
                $label = $item['label'] ?? '';
                $isActive = $activeNav === $label || $activeNav === ($item['id'] ?? $item['key'] ?? null);
                $url = $item['url'] ?? null;
                $icon = $item['icon'] ?? null;
            @endphp

            @if ($url)
                <a
                    href="{{ $url }}"
                    aria-label="{{ $label }}"
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
                        <span class="central-sidebar-label">{{ $label }}</span>
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
                        <span class="central-sidebar-label">{{ $label }}</span>
                    </span>
                </span>
            @endif
        @endforeach
    </nav>

    <div class="central-sidebar-desktop-controls border-t border-admin-border p-3">
        <button
            type="button"
            class="flex w-full items-center gap-2 rounded-admin-input px-3 py-2 text-sm font-medium text-admin-muted hover:bg-admin-surface-muted hover:text-admin-text focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary"
            aria-label="Collapse navigation"
            aria-pressed="false"
            data-central-sidebar-collapse
        >
            <x-ui.icon name="chevron-double-left" size="sm" data-central-sidebar-collapse-icon />
            <span class="central-sidebar-label">Collapse navigation</span>
        </button>
    </div>
</aside>
