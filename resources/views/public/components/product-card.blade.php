@php
    $cardVariant = in_array($variant ?? 'grid', ['grid', 'list'], true) ? ($variant ?? 'grid') : 'grid';
    $mediaPayload = is_array($product['media'] ?? null)
        ? $product['media']
        : (is_array(data_get($product, 'payload.media')) ? data_get($product, 'payload.media') : []);
    $mainMedia = is_array($mediaPayload['main'] ?? null) ? $mediaPayload['main'] : [];
    $imageUrl = is_string($mainMedia['url'] ?? null) && $mainMedia['url'] !== '' ? $mainMedia['url'] : null;
    $summary = is_array($product['summary'] ?? null)
        ? $product['summary']
        : (is_array(data_get($product, 'payload.summary')) ? data_get($product, 'payload.summary') : []);
    $keySpecs = is_array($summary['key_specs'] ?? null) ? array_slice($summary['key_specs'], 0, 3) : [];
    $rating = is_array($product['rating'] ?? null)
        ? $product['rating']
        : (is_array($summary['rating'] ?? null) ? $summary['rating'] : null);
    $ratingValue = is_array($rating) ? ($rating['value'] ?? $rating['average'] ?? null) : null;
@endphp

<article
    data-product-card
    data-variant="{{ $cardVariant }}"
    @class([
        'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md',
        'flex flex-col sm:flex-row' => $cardVariant === 'list',
        'flex flex-col' => $cardVariant === 'grid',
    ])
>
    <a
        href="{{ $product['url'] ?? '#' }}"
        @class([
            'flex shrink-0 items-center justify-center overflow-hidden bg-slate-100',
            'aspect-[4/3] w-full sm:aspect-square sm:w-48' => $cardVariant === 'list',
            'aspect-[4/3] w-full' => $cardVariant === 'grid',
        ])
        tabindex="-1"
        aria-hidden="true"
    >
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $mainMedia['alt'] ?? $product['title'] ?? 'Product image' }}" loading="lazy" class="h-full w-full object-contain p-3">
        @else
            <span data-product-card-placeholder class="text-3xl text-slate-300">◇</span>
        @endif
    </a>

    <div class="flex min-w-0 flex-1 flex-col p-5">
        <h3 class="font-semibold leading-6 text-slate-950">
            <a href="{{ $product['url'] ?? '#' }}" class="transition hover:text-blue-600">{{ $product['title'] ?? 'Untitled product' }}</a>
        </h3>

        @if ($keySpecs !== [])
            <ul class="mt-3 space-y-1 text-sm text-slate-600">
                @foreach ($keySpecs as $spec)
                    <li>{{ is_array($spec) ? ($spec['display_value'] ?? $spec['value'] ?? '') : $spec }}</li>
                @endforeach
            </ul>
        @endif

        <div class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-1 pt-4 text-sm">
            @if (is_numeric($ratingValue))
                <span class="font-semibold text-amber-600">★ {{ $ratingValue }}</span>
                @if (is_numeric($rating['review_count'] ?? null))
                    <span class="text-slate-500">{{ (int) $rating['review_count'] }} reviews</span>
                @endif
            @endif
            @if (filled($product['price_placeholder'] ?? null))
                <span class="font-medium text-slate-500">{{ $product['price_placeholder'] }}</span>
            @endif
        </div>
    </div>
</article>
