@php
    $centralUser ??= auth()->user();
@endphp

<header
    class="flex h-[var(--height-foundation-header)] items-center justify-between gap-3 border-b border-admin-border bg-admin-surface px-admin-page text-admin-text"
    data-central-header
    data-admin-shell-header
>
    <div class="flex min-w-0 items-center gap-2">
        <button
            type="button"
            class="central-sidebar-mobile-open rounded-admin-input p-2 text-admin-muted hover:bg-admin-surface-muted hover:text-admin-text focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary"
            aria-label="Open navigation"
            aria-controls="central-navigation"
            aria-expanded="false"
            data-central-sidebar-open
        >
            <x-ui.icon name="bars-3" size="md" />
        </button>

        <nav class="min-w-0 text-sm text-admin-muted" aria-label="Breadcrumbs" data-central-header-breadcrumbs>
            @hasSection('breadcrumbs')
                @yield('breadcrumbs')
            @else
                <span aria-current="page">{{ $documentTitle }}</span>
            @endif
        </nav>
    </div>

    <div class="flex min-w-0 flex-wrap items-center justify-end gap-admin-field">
        <span
            class="hidden items-center gap-2 rounded-admin-input border border-admin-border bg-admin-surface-muted px-3 py-2 text-sm text-admin-muted sm:inline-flex"
            aria-label="Search unavailable"
            data-central-search-state="unavailable"
        >
            <x-ui.icon name="magnifying-glass" size="sm" />
            <span>Search unavailable</span>
        </span>

        <span
            class="hidden items-center gap-2 rounded-admin-input border border-admin-border bg-admin-surface-muted px-3 py-2 text-sm text-admin-muted md:inline-flex"
            aria-label="Notifications unavailable"
            data-central-notifications-state="unavailable"
        >
            <x-ui.icon name="bell" size="sm" />
            <span>Notifications unavailable</span>
        </span>

        @if ($centralUser instanceof \App\Models\User)
            @include('central.components.user-menu', ['user' => $centralUser])
        @endif
    </div>
</header>
