<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        <x-admin.card title="Local presentation override" description="Canonical catalog fields remain read-only.">
            <div class="grid gap-4 md:grid-cols-2">
                <select wire:model="entityType"><option value="product">Product</option><option value="category">Category</option><option value="brand">Brand</option></select>
                <input wire:model="entityId" type="number" placeholder="Entity ID">
                <select wire:model.live="field">@foreach(\App\Services\Sites\AllowedSiteOverrideFields::FIELDS as $fieldOption)<option value="{{ $fieldOption }}">{{ str($fieldOption)->headline() }}</option>@endforeach</select>
                <input wire:model="localeCode" placeholder="Locale (optional)">
                <input wire:model="value" placeholder="Override value">
                <input wire:model="reason" placeholder="Reason (optional)">
            </div>
            @if($this->field === 'local_slug')<p class="mt-3 text-sm text-gray-500">Use a lowercase slug without spaces or leading/trailing slashes.</p>@endif
        </x-admin.card>
        <div class="flex justify-end"><x-filament::button type="submit">Save override</x-filament::button></div>
    </form>
    <x-admin.card title="Existing overrides"><div class="space-y-2">@foreach($this->getOverrides() as $override)<div>{{ $override->entity_type }} #{{ $override->entity_id }} · {{ $override->field }} · {{ $override->locale_code ?? 'global' }}</div>@endforeach</div></x-admin.card>
</x-filament-panels::page>
