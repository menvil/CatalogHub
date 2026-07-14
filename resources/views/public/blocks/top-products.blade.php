<section data-theme-block="top_products" aria-labelledby="top-products-title">
    <h2 id="top-products-title" class="text-2xl font-bold tracking-tight">{{ $config['title'] ?? 'Top products' }}</h2>
    @if (($data['products'] ?? []) !== [])
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($data['products'] as $product)
                @include('public.components.product-card', ['product' => $product, 'variant' => 'grid'])
            @endforeach
        </div>
    @else
        <p class="mt-4 text-slate-600">Products will appear when projections are available.</p>
    @endif
</section>
