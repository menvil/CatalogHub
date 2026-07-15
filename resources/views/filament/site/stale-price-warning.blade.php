@php
    $warning = $this->getStalePriceWarning();
@endphp

@if ($warning->hasWarning())
    <section data-stale-price-warning class="rounded-xl border border-amber-300 bg-amber-50 p-5 text-amber-950">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold">Price data needs attention</h2>
                <p class="mt-1 text-sm">
                    Stale offers: {{ $warning->staleOffersCount }} ·
                    Expired offers: {{ $warning->expiredOffersCount }} ·
                    Affected products: {{ $warning->affectedProductsCount }}
                </p>
                <p class="mt-1 text-xs text-amber-800">
                    Last successful update: {{ $warning->lastSuccessfulUpdateAt?->diffForHumans() ?? 'unknown' }}
                </p>
            </div>
            <a href="{{ $this->getStalePriceReviewUrl() }}" class="inline-flex rounded-lg bg-amber-700 px-4 py-2 text-sm font-semibold text-white">Review stale prices</a>
        </div>
    </section>
@endif
