<footer data-public-footer class="border-t border-slate-200 bg-white">
    <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
        <p>&copy; {{ now()->year }} {{ $site->name ?? config('app.name', 'CatalogHub') }}</p>
        <p>Projection-powered product discovery.</p>
    </div>
</footer>
