<section data-theme-block="popular_categories" aria-labelledby="popular-categories-title">
    <div class="flex items-end justify-between gap-4">
        <h2 id="popular-categories-title" class="text-2xl font-bold tracking-tight">{{ $config['title'] ?? 'Popular categories' }}</h2>
    </div>
    @if (($data['categories'] ?? []) !== [])
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($data['categories'] as $category)
                @include('public.components.category-card', ['category' => $category])
            @endforeach
        </div>
    @else
        <p class="mt-4 text-slate-600">Categories will appear when projections are available.</p>
    @endif
</section>
