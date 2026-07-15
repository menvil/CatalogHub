<x-filament-panels::page>
    @include('filament.site.stale-price-warning')

    @php($labels = [
        'visible_products' => 'Visible products',
        'hidden_products' => 'Hidden products',
        'enabled_categories' => 'Enabled categories',
        'enabled_locales' => 'Enabled locales',
        'enabled_features' => 'Enabled features',
        'products_without_local_seo' => 'Products without local SEO',
        'products_without_prices' => 'Products without prices',
        'stale_products' => 'Stale products',
        'pending_reviews' => 'Pending reviews',
        'new_leads' => 'New leads',
        'sync_status' => 'Sync status',
    ])
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach($this->getMetrics() as $key => $value)
            <x-admin.card :title="$labels[$key] ?? ucfirst(str_replace('_', ' ', $key))">
                <div class="text-2xl font-semibold">{{ $value }}</div>
            </x-admin.card>
        @endforeach
    </div>
</x-filament-panels::page>
