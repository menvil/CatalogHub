@props([
    'contextLabel' => 'Central Admin',
    'searchPlaceholder' => 'Search admin workspace',
])

<header
    {{ $attributes->class('flex flex-col gap-3 border-b border-admin-border bg-admin-surface px-admin-page py-4 text-admin-text lg:flex-row lg:items-center lg:justify-between') }}
>
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-admin-muted">{{ $contextLabel }}</p>
        <div class="mt-1">
            {{ $title ?? '' }}
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-admin-field">
        <label class="sr-only" for="{{ $attributes->get('search-id', 'admin-global-search') }}">Global search</label>
        <input
            id="{{ $attributes->get('search-id', 'admin-global-search') }}"
            type="search"
            disabled
            placeholder="{{ $searchPlaceholder }}"
            class="min-w-0 rounded-admin-input border border-admin-border bg-admin-surface-muted px-3 py-2 text-sm text-admin-muted placeholder:text-admin-muted focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary sm:w-72"
        >

        <button
            type="button"
            disabled
            class="rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm font-medium text-admin-muted"
            aria-label="Notifications placeholder"
        >
            Notifications
        </button>

        <button
            type="button"
            disabled
            class="rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm font-medium text-admin-muted"
            aria-label="Profile placeholder"
        >
            Profile
        </button>
    </div>
</header>
