<section data-theme-block="top_products" aria-labelledby="top-products-title">
    <h2 id="top-products-title" class="text-2xl font-bold tracking-tight">{{ $config['title'] ?? 'Top products' }}</h2>
    @if (($data['products'] ?? []) !== [])
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($data['products'] as $product)
                <a href="/{{ $locale }}/products/{{ $product['slug'] }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <h3 class="font-semibold">{{ $product['title'] }}</h3>
                </a>
            @endforeach
        </div>
    @else
        <p class="mt-4 text-slate-600">Products will appear when projections are available.</p>
    @endif
</section>
