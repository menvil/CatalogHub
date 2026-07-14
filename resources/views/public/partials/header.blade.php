<header data-public-header class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <a href="/" class="text-lg font-bold tracking-tight text-slate-950">
            {{ $site->name ?? config('app.name', 'CatalogHub') }}
        </a>

        <nav aria-label="Primary navigation" class="flex items-center gap-4 text-sm font-medium text-slate-600">
            <a href="/" class="transition hover:text-slate-950">Home</a>
            <a href="/search" class="transition hover:text-slate-950">Search</a>
        </nav>
    </div>
</header>
