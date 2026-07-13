<x-filament-panels::page>
    <x-admin.card title="Brand visibility" description="Hide or allow brands only for this site.">
        @php($brandPage = $this->getBrandPage())
        @php($brands = $brandPage['brands'])
        <label for="brand-visibility-search" class="mb-4 block space-y-2">
            <span>Search brands</span>
            <input id="brand-visibility-search" wire:model.live.debounce.300ms="search" type="search" placeholder="Search by brand name">
        </label>
        <div class="space-y-3">
            @foreach($brands as $brand)
                @php($allowed = $brandPage['allowedById'][$brand->id])
                <div wire:key="brand-visibility-{{ $brand->getKey() }}" class="flex items-center justify-between border-b py-3 dark:border-gray-800">
                    <span>{{ $brand->name }}</span>
                    <x-filament::button wire:click="toggle({{ $brand->id }})" :color="$allowed ? 'danger' : 'success'">{{ $allowed ? 'Hide' : 'Allow' }}</x-filament::button>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $brands->links() }}</div>
    </x-admin.card>
</x-filament-panels::page>
