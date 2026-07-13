<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        <x-admin.card title="Local presentation override" description="Canonical catalog fields remain read-only.">
            <div class="grid gap-4 md:grid-cols-2">
                <label for="override-entity-type" class="space-y-2">
                    <span>Entity type</span>
                    <select id="override-entity-type" wire:model="entityType">
                        @foreach(\App\Services\Sites\AllowedSiteOverrideFields::ENTITY_TYPES as $entityTypeOption)
                            <option value="{{ $entityTypeOption }}">{{ str($entityTypeOption)->headline() }}</option>
                        @endforeach
                    </select>
                    @error('entityType') <span class="block text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label for="override-entity-id" class="space-y-2">
                    <span>Entity ID</span>
                    <input id="override-entity-id" wire:model="entityId" type="number">
                    @error('entityId') <span class="block text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label for="override-field" class="space-y-2">
                    <span>Field</span>
                    <select id="override-field" wire:model.live="field">
                        @foreach(\App\Services\Sites\AllowedSiteOverrideFields::FIELDS as $fieldOption)
                            <option value="{{ $fieldOption }}">{{ str($fieldOption)->headline() }}</option>
                        @endforeach
                    </select>
                    @error('field') <span class="block text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label for="override-locale" class="space-y-2">
                    <span>Locale (optional)</span>
                    <input id="override-locale" wire:model="localeCode" placeholder="de-DE">
                    @error('localeCode') <span class="block text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label for="override-value" class="space-y-2">
                    <span>Override value</span>
                    @if(in_array($this->field, ['meta_description', 'intro_text', 'hero_text'], true))
                        <textarea id="override-value" wire:model="value" rows="5" placeholder="Leave empty to clear"></textarea>
                    @else
                        <input id="override-value" wire:model="value" placeholder="Leave empty to clear">
                    @endif
                    @error('value') <span class="block text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label for="override-reason" class="space-y-2">
                    <span>Reason (optional)</span>
                    <input id="override-reason" wire:model="reason">
                    @error('reason') <span class="block text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
            </div>
            @if($this->field === 'local_slug')
                <p class="mt-3 text-sm text-gray-500">Use a lowercase slug without spaces or leading/trailing slashes.</p>
            @endif
            @if($this->field === 'local_title')
                <p class="mt-3 text-sm text-gray-500">This display title takes precedence over the translated central title for the selected locale.</p>
            @endif
        </x-admin.card>
        <div class="flex justify-end"><x-filament::button type="submit">Save override</x-filament::button></div>
    </form>

    <x-admin.card title="Existing overrides">
        <div class="space-y-2">
            @foreach($this->getOverrides() as $override)
                <div>{{ $override->entity_type }} #{{ $override->entity_id }} · {{ $override->field }} · {{ $override->locale_code !== '' ? $override->locale_code : 'global' }}</div>
            @endforeach
        </div>
    </x-admin.card>
</x-filament-panels::page>
