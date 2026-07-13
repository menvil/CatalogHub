<x-filament-panels::page>
    <x-admin.card title="Product visibility" description="Local publication state; central product data is read-only.">
        <div class="space-y-3">@foreach($this->getProducts() as $product)
            @php($state = $this->getRecord()->products()->where('central_product_id', $product->id)->first())
            <div class="flex items-center justify-between gap-4 border-b border-gray-200 py-3 dark:border-gray-800"><div><strong>{{ $product->name }}</strong><div class="text-sm text-gray-500">{{ $product->brand?->name }} · {{ $state?->visibility ?? 'hidden' }}</div></div><div class="flex gap-2">@foreach(['visible','hidden','excluded'] as $visibility)<x-filament::button size="sm" wire:click="setVisibility({{ $product->id }}, '{{ $visibility }}')">{{ ucfirst($visibility) }}</x-filament::button>@endforeach<x-filament::button size="sm" color="warning" wire:click="toggleFeatured({{ $product->id }})">{{ $state?->is_featured ? 'Unfeature' : 'Feature' }}</x-filament::button></div></div>
        @endforeach</div>
    </x-admin.card>
</x-filament-panels::page>
