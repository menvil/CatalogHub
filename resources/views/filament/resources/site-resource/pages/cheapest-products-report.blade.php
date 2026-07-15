<x-filament-panels::page>
    @php
        $products = $this->getProducts();
    @endphp

    <form method="GET" class="grid gap-4 rounded-xl border border-gray-200 bg-white p-4 md:grid-cols-3 xl:grid-cols-6">
        @foreach (['category_id' => ['Category', $this->getCategoryOptions()], 'brand_id' => ['Brand', $this->getBrandOptions()], 'merchant_id' => ['Merchant', $this->getMerchantOptions()]] as $name => [$label, $options])
            <label class="text-sm font-medium">{{ $label }}
                <select name="{{ $name }}" class="mt-1 block w-full rounded-lg border-gray-300"><option value="">All</option>@foreach ($options as $id => $option)<option value="{{ $id }}" @selected((string) request()->query($name) === (string) $id)>{{ $option }}</option>@endforeach</select>
            </label>
        @endforeach
        <label class="text-sm font-medium">Freshness
            <select name="freshness" class="mt-1 block w-full rounded-lg border-gray-300"><option value="">All</option>@foreach (\App\Enums\PriceFreshnessStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request()->query('freshness') === $status->value)>{{ str($status->value)->headline() }}</option>@endforeach</select>
        </label>
        <label class="flex items-end gap-2 pb-2 text-sm font-medium"><input type="checkbox" name="in_stock" value="1" @checked(request()->boolean('in_stock'))> In stock only</label>
        <div class="flex items-end"><button class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white">Apply</button></div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-50"><tr><th class="px-4 py-3">Product</th><th class="px-4 py-3">Category</th><th class="px-4 py-3">Brand</th><th class="px-4 py-3">Min price</th><th class="px-4 py-3">Best merchant</th><th class="px-4 py-3">Offers</th><th class="px-4 py-3">Updated</th><th class="px-4 py-3">Freshness</th></tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($products as $product)
                    <tr><td class="px-4 py-3 font-medium">{{ $product->getAttribute('product_name') }}</td><td class="px-4 py-3">{{ $product->getAttribute('category_name') ?? '—' }}</td><td class="px-4 py-3">{{ $product->getAttribute('brand_name') ?? '—' }}</td><td class="px-4 py-3 font-semibold">{{ $this->formattedPrice($product) }}</td><td class="px-4 py-3">{{ $product->getAttribute('best_merchant') ?? 'Unknown' }}</td><td class="px-4 py-3">{{ $product->offers_count }}</td><td class="px-4 py-3">{{ $product->last_price_update_at?->diffForHumans() ?? 'Never' }}</td><td class="px-4 py-3">{{ $this->freshnessLabel($product) }}</td></tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No priced products match the filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $products->links() }}
</x-filament-panels::page>
