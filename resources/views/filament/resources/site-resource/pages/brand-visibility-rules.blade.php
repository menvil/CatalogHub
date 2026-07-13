<x-filament-panels::page>
    <x-admin.card title="Brand visibility" description="Hide or allow brands only for this site.">
        @php($visibilityService = app(\App\Services\Sites\SiteBrandVisibilityService::class))
        <div class="space-y-3">
            @foreach($this->getBrands() as $brand)
                @php($allowed = $visibilityService->allows($this->getRecord(), $brand))
                <div class="flex items-center justify-between border-b py-3 dark:border-gray-800">
                    <span>{{ $brand->name }}</span>
                    <x-filament::button wire:click="toggle({{ $brand->id }})" :color="$allowed ? 'danger' : 'success'">{{ $allowed ? 'Hide' : 'Allow' }}</x-filament::button>
                </div>
            @endforeach
        </div>
    </x-admin.card>
</x-filament-panels::page>
