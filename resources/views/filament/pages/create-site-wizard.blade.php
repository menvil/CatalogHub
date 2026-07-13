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
                <input wire:model="code" placeholder="Site code" class="rounded-md border-gray-300 dark:bg-gray-950">
                <input wire:model="name" placeholder="Site name" class="rounded-md border-gray-300 dark:bg-gray-950">
                <input wire:model="domain" placeholder="Domain (optional)" class="rounded-md border-gray-300 dark:bg-gray-950">
                <select wire:model.live="marketId" class="rounded-md border-gray-300 dark:bg-gray-950">
                    <option value="">Select market</option>
                    @foreach($this->getMarkets() as $market)
                        <option value="{{ $market->id }}">{{ $market->name }}</option>
                    @endforeach
                </select>
                <select wire:model="mode" class="rounded-md border-gray-300 dark:bg-gray-950">
                    <option value="single_category">Single category</option>
                    <option value="multi_category">Multi category</option>
                </select>
                <select wire:model="defaultLocale" class="rounded-md border-gray-300 dark:bg-gray-950">
                    <option value="">Default locale</option>
                    @foreach($this->getLocales()->whereIn('code', $enabledLocales) as $locale)
                        <option value="{{ $locale->code }}">{{ $locale->name }}</option>
                    @endforeach
                </select>
            </div>

            @if($this->getSelectedMarket())
                <p class="mt-3 text-sm text-gray-500">{{ $this->getSelectedMarket()->country_code }} · {{ $this->getSelectedMarket()->currency_code }} · {{ $this->getSelectedMarket()->default_locale }} · {{ $this->getSelectedMarket()->timezone }}</p>
            @endif

            <p class="mt-2 text-sm text-gray-500">Single category requires exactly one enabled category; multi category requires at least one.</p>
            <div class="mt-5">
                <strong>Locales</strong>
                @foreach($this->getLocales() as $locale)
                    <label class="ml-4"><input wire:model="enabledLocales" type="checkbox" value="{{ $locale->code }}"> {{ $locale->code }}</label>
                @endforeach
            </div>
            <div class="mt-5">
                <strong>Categories</strong>
                @foreach($this->getCategories() as $category)
                    <label class="ml-4"><input wire:model="enabledCategories" type="checkbox" value="{{ $category->id }}"> {{ $category->name }}</label>
                @endforeach
            </div>
            <div class="mt-5">
                <strong>Features</strong>
                @foreach(array_keys($features) as $feature)
                    <label class="ml-4"><input wire:model="features.{{ $feature }}" type="checkbox"> {{ str($feature)->headline() }}</label>
                @endforeach
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
