<x-filament-panels::page>
    @php
        $coverage = $this->getCoverage();
    @endphp

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-admin.card title="Visible products"><div class="text-2xl font-semibold">{{ $coverage->totalVisibleProducts }}</div></x-admin.card>
        <x-admin.card title="Products with offers"><div class="text-2xl font-semibold">{{ $coverage->productsWithOffers }}</div></x-admin.card>
        <x-admin.card title="Products without offers"><div class="text-2xl font-semibold">{{ $coverage->productsWithoutOffers }}</div></x-admin.card>
        <x-admin.card title="Coverage"><div class="text-2xl font-semibold">{{ number_format($coverage->coveragePercent, 2) }}%</div></x-admin.card>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-admin.card title="Coverage by category">
            <table class="min-w-full text-left text-sm">
                <thead><tr><th class="py-2">Category</th><th>Covered</th><th>Coverage</th></tr></thead>
                <tbody>
                    @foreach ($coverage->categoryCoverage as $category)
                        <tr class="border-t border-gray-200"><td class="py-2 font-medium">{{ $category['name'] }}</td><td>{{ $category['covered'] }} / {{ $category['total'] }}</td><td>{{ number_format($category['percent'], 2) }}%</td></tr>
                    @endforeach
                </tbody>
            </table>
        </x-admin.card>

        <x-admin.card title="Coverage by source">
            <table class="min-w-full text-left text-sm">
                <thead><tr><th class="py-2">Source</th><th>Products</th><th>Coverage</th></tr></thead>
                <tbody>
                    @foreach ($coverage->sourceCoverage as $source)
                        <tr class="border-t border-gray-200"><td class="py-2 font-medium">{{ $source['name'] }}</td><td>{{ $source['covered'] }}</td><td>{{ number_format($source['percent'], 2) }}%</td></tr>
                    @endforeach
                </tbody>
            </table>
        </x-admin.card>
    </div>

    <x-admin.card title="Freshness coverage">
        <div class="flex flex-wrap gap-6 text-sm"><span>Stale offers: <strong>{{ $coverage->staleOffersCount }}</strong></span><span>Expired offers: <strong>{{ $coverage->expiredOffersCount }}</strong></span></div>
    </x-admin.card>
</x-filament-panels::page>
