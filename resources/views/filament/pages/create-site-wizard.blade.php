<x-filament-panels::page>
    <form wire:submit="createSite" class="space-y-6">
        @php($stepLabels = ['Basic Info', 'Market', 'Mode', 'Locales', 'Categories', 'Features', 'Review & Create'])
        <x-admin.stepper-wizard
            :steps="collect($stepLabels)->map(fn ($label, $index) => [
                'key' => (string) $index,
                'label' => $label,
                'status' => $index < $currentStep ? 'completed' : ($index === $currentStep ? 'current' : 'pending'),
            ])->all()"
            :current-step="(string) $currentStep"
        />

        <x-admin.card title="Site configuration" description="Configure the local portal and review it before creation.">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="site-code" class="mb-1 block text-sm font-medium">Site code</label>
                    <input id="site-code" wire:model="code" class="w-full rounded-md border-gray-300 dark:bg-gray-950">
                    @error('code') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="site-name" class="mb-1 block text-sm font-medium">Site name</label>
                    <input id="site-name" wire:model="name" class="w-full rounded-md border-gray-300 dark:bg-gray-950">
                    @error('name') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="site-domain" class="mb-1 block text-sm font-medium">Domain (optional)</label>
                    <input id="site-domain" wire:model="domain" class="w-full rounded-md border-gray-300 dark:bg-gray-950">
                    @error('domain') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="site-market" class="mb-1 block text-sm font-medium">Market</label>
                    <select id="site-market" wire:model.live="marketId" class="w-full rounded-md border-gray-300 dark:bg-gray-950">
                        <option value="">Select market</option>
                        @foreach($this->getMarkets() as $market)
                            <option value="{{ $market->id }}">{{ $market->name }}</option>
                        @endforeach
                    </select>
                    @error('marketId') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="site-mode" class="mb-1 block text-sm font-medium">Mode</label>
                    <select id="site-mode" wire:model="mode" class="w-full rounded-md border-gray-300 dark:bg-gray-950">
                        <option value="single_category">Single category</option>
                        <option value="multi_category">Multi category</option>
                    </select>
                    @error('mode') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="site-default-locale" class="mb-1 block text-sm font-medium">Default locale</label>
                    <select id="site-default-locale" wire:model="defaultLocale" class="w-full rounded-md border-gray-300 dark:bg-gray-950">
                        <option value="">Default locale</option>
                        @foreach($this->getLocales()->whereIn('code', $enabledLocales) as $locale)
                            <option value="{{ $locale->code }}">{{ $locale->name }}</option>
                        @endforeach
                    </select>
                    @error('defaultLocale') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
            </div>

            @if($this->getSelectedMarket())
                <p class="mt-3 text-sm text-gray-500">{{ $this->getSelectedMarket()->country_code }} · {{ $this->getSelectedMarket()->currency_code }} · {{ $this->getSelectedMarket()->default_locale }} · {{ $this->getSelectedMarket()->timezone }}</p>
            @endif

            <p class="mt-2 text-sm text-gray-500">Single category requires exactly one enabled category; multi category requires at least one.</p>
            <div class="mt-5">
                <strong>Locales</strong>
                @foreach($this->getLocales() as $locale)
                    <label wire:key="site-locale-{{ $locale->id }}" class="ml-4"><input wire:model.live="enabledLocales" type="checkbox" value="{{ $locale->code }}"> {{ $locale->code }}</label>
                @endforeach
                @error('enabledLocales') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                @error('enabledLocales.*') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
            </div>
            <div class="mt-5">
                <strong>Categories</strong>
                @foreach($this->getCategories() as $category)
                    <label wire:key="site-category-{{ $category->id }}" class="ml-4"><input wire:model="enabledCategories" type="checkbox" value="{{ $category->id }}"> {{ $category->name }}</label>
                @endforeach
                @error('enabledCategories') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                @error('enabledCategories.*') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
            </div>
            <div class="mt-5">
                <strong>Features</strong>
                @foreach(array_keys($features) as $feature)
                    <label wire:key="site-feature-{{ $feature }}" class="ml-4"><input wire:model="features.{{ $feature }}" type="checkbox"> {{ str($feature)->headline() }}</label>
                @endforeach
                @error('features') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                @error('features.*') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
            </div>
            @if($errors->any())
                <div class="mt-4 text-danger-600">Please correct the highlighted site configuration.</div>
            @endif
        </x-admin.card>

        <div class="flex justify-between gap-3">
            <div>
                @if($currentStep > 0)
                    <x-filament::button type="button" color="gray" wire:click="previousStep">Previous</x-filament::button>
                @endif
            </div>
            <div>
                @if($currentStep < 6)
                    <x-filament::button type="button" wire:click="nextStep">Next</x-filament::button>
                @else
                    <x-filament::button type="submit">Review & Create</x-filament::button>
                @endif
            </div>
        </div>

        @if($createdSiteId)
            <x-admin.card variant="success" title="Site created">Site #{{ $createdSiteId }} was created.</x-admin.card>
        @endif
    </form>
</x-filament-panels::page>
