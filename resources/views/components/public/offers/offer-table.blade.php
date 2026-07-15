<div data-offer-table class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
            <tr>
                <th scope="col" class="px-4 py-3 sm:px-6">Merchant</th>
                <th scope="col" class="px-4 py-3">Price</th>
                <th scope="col" class="px-4 py-3">Availability</th>
                <th scope="col" class="px-4 py-3">Delivery</th>
                <th scope="col" class="px-4 py-3">Updated</th>
                <th scope="col" class="px-4 py-3 sm:px-6">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 bg-white">
            @foreach ($orderedOffers as $offer)
                <tr @if ($bestOffer?->is($offer)) data-best-offer class="bg-blue-50/50" @endif>
                    <td class="whitespace-nowrap px-4 py-4 font-semibold text-slate-950 sm:px-6">
                        {{ $offer->merchant?->name ?? 'Merchant' }}
                        @if ($bestOffer?->is($offer))
                            <span class="ml-2 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">Best</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-4 font-bold tabular-nums text-slate-950">{{ $formattedPrices[$offer->getKey()] }}</td>
                    <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ str($offer->availability->value)->headline() }}</td>
                    <td class="whitespace-nowrap px-4 py-4 text-slate-500">
                        <span class="block">{{ $formattedDeliveryPrices[$offer->getKey()] }}</span>
                        <span class="mt-1 block text-xs">{{ $formattedDeliveryTimes[$offer->getKey()] }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-4">
                        <x-public.price-freshness-badge :status="$freshness[$offer->getKey()] ?? \App\Enums\PriceFreshnessStatus::Unknown" />
                    </td>
                    <td class="whitespace-nowrap px-4 py-4 sm:px-6">
                        <span aria-disabled="true" class="inline-flex cursor-not-allowed rounded-lg bg-slate-200 px-3 py-2 font-semibold text-slate-500">Go to shop</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
