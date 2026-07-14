<section data-theme-block="popular_categories" aria-labelledby="popular-categories-title">
    <div class="flex items-end justify-between gap-4">
        <h2 id="popular-categories-title" class="text-2xl font-bold tracking-tight">{{ $config['title'] ?? 'Popular categories' }}</h2>
    </div>
    @if (($data['categories'] ?? []) !== [])
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($data['categories'] as $category)
                <a href="{{ $category['url'] }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <h3 class="font-semibold">{{ $category['title'] }}</h3>
                    @if (filled($category['description']))
                        <p class="mt-2 text-sm text-slate-600">{{ $category['description'] }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    @else
        <p class="mt-4 text-slate-600">Categories will appear when projections are available.</p>
    @endif
</section>
