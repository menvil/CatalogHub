<x-filament-panels::page>
    @php
        $products = $this->getProducts();
        $categoryId = request()->query('category_id');
        $brandId = request()->query('brand_id');
    @endphp

    <form method="GET" class="grid gap-4 rounded-xl border border-gray-200 bg-white p-4 md:grid-cols-3">
        <label class="text-sm font-medium">Category
            <select name="category_id" class="mt-1 block w-full rounded-lg border-gray-300">
                <option value="">All categories</option>
                @foreach ($this->getCategoryOptions() as $id => $label)
                    <option value="{{ $id }}" @selected((string) $categoryId === (string) $id)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-medium">Brand
            <select name="brand_id" class="mt-1 block w-full rounded-lg border-gray-300">
                <option value="">All brands</option>
                @foreach ($this->getBrandOptions() as $id => $label)
                    <option value="{{ $id }}" @selected((string) $brandId === (string) $id)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex items-end"><button class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white">Apply filters</button></div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-50"><tr><th class="px-4 py-3">Product</th><th class="px-4 py-3">Category</th><th class="px-4 py-3">Brand</th><th class="px-4 py-3">Projection status</th><th class="px-4 py-3">Last synced</th><th class="px-4 py-3">Actions</th></tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($products as $siteProduct)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $siteProduct->product->name }}</td>
                        <td class="px-4 py-3">{{ $siteProduct->product->category?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $siteProduct->product->brand?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ str((string) ($siteProduct->getAttribute('projection_status') ?? 'missing'))->headline() }}</td>
                        <td class="px-4 py-3">{{ $siteProduct->getAttribute('projection_built_at') ?? 'Never' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                @if ($siteProduct->getAttribute('projection_id'))
                                    <a class="font-semibold text-primary-600" href="{{ \App\Filament\Resources\SiteProductProjectionResource::getUrl('view', ['record' => $siteProduct->getAttribute('projection_id')]) }}">Projection</a>
                                @endif
                                <a class="font-semibold text-primary-600" href="{{ \App\Filament\Resources\SiteResource\Pages\OfferProviderPreview::getUrl(['record' => $this->getRecord()]) }}">Price settings</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Every visible product has a current offer.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $products->links() }}
</x-filament-panels::page>
