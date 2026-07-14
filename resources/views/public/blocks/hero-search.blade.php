<section data-theme-block="hero_search" class="overflow-hidden rounded-3xl bg-slate-950 px-6 py-14 text-white sm:px-10 lg:px-16 lg:py-20">
    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-300">{{ $site->name }}</p>
    <h1 class="mt-4 max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">
        {{ $config['title'] ?? 'Find products worth comparing' }}
    </h1>
    @if (filled($config['subtitle'] ?? null))
        <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-300">{{ $config['subtitle'] }}</p>
    @endif
    <form action="/{{ $locale }}/search" method="get" role="search" class="mt-8 flex max-w-2xl gap-3">
        <label class="sr-only" for="homepage-search">Search products</label>
        <input id="homepage-search" name="q" type="search" placeholder="{{ $config['search_placeholder'] ?? 'Search products' }}" class="min-w-0 flex-1 rounded-xl border-0 bg-white px-4 py-3 text-slate-950">
        <button class="rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white transition hover:bg-blue-500">Search</button>
    </form>
</section>
