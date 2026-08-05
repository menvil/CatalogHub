<header
    class="flex min-h-16 flex-wrap items-center justify-between gap-3 border-b border-admin-border bg-admin-surface px-admin-page py-3 text-admin-text"
    data-site-header
>
    <div class="flex min-w-0 items-center gap-2">
        <button
            type="button"
            class="site-sidebar-mobile-open rounded-admin-input p-2 text-admin-muted hover:bg-admin-surface-muted hover:text-admin-text focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary"
            aria-label="Open navigation"
            aria-controls="site-navigation"
            aria-expanded="false"
            data-site-sidebar-open
        >
            <x-ui.icon name="bars-3" size="md" />
        </button>

        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-admin-muted">CatalogHub</p>
            <p class="truncate text-lg font-semibold text-admin-text">Site Admin</p>
        </div>
    </div>

    <div class="flex min-w-0 flex-wrap items-center justify-end gap-admin-field">
        <span class="hidden rounded-admin-input border border-admin-border bg-admin-surface-muted px-3 py-2 text-sm text-admin-muted sm:inline-flex">
            Search site workspace unavailable
        </span>
        <span class="hidden rounded-admin-input border border-admin-border bg-admin-surface-muted px-3 py-2 text-sm text-admin-muted md:inline-flex">
            Notifications unavailable
        </span>

        @if ($siteAdminUser instanceof \App\Models\User)
            <span class="max-w-48 truncate text-sm font-medium text-admin-text">{{ $siteAdminUser->name }}</span>
            @if (\Illuminate\Support\Facades\Route::has('filament.site.auth.logout'))
                <form method="post" action="{{ route('filament.site.auth.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-admin-input border border-admin-border px-3 py-2 text-sm font-medium text-admin-text">Logout</button>
                </form>
            @endif
        @endif
    </div>
</header>
