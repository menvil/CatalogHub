<section data-offers-block class="mt-10 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" aria-labelledby="offers-heading-{{ $productProjection->getKey() }}">
    <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Offers</p>
        <h2 id="offers-heading-{{ $productProjection->getKey() }}" class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Where to buy</h2>
    </div>

    @if ($offers->isEmpty())
        <div class="px-6 py-8 text-slate-600 sm:px-8">
            <p class="font-semibold text-slate-900">No current offers</p>
            <p class="mt-1 text-sm">We do not have a reliable price for this product right now.</p>
        </div>
    @else
        @if ($bestOffer)
            <div class="border-b border-blue-100 bg-blue-50 px-6 py-4 sm:px-8">
                <p class="text-sm font-semibold text-blue-900">Best offer: {{ $bestOffer->merchant?->name }} · {{ $formattedPrices[$bestOffer->getKey()] }}</p>
            </div>
        @endif

        <x-public.offer-table
            :offers="$offers"
            :freshness="$freshness"
            :best-offer="$bestOffer"
            :locale="$locale"
        />
    @endif
</section>
