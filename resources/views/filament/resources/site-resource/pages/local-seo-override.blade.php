<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        <x-admin.card title="Local SEO fields" description="Empty values remove the local override and preserve central translations.">
            <div class="grid gap-4 md:grid-cols-2">
                <label for="seo-entity-type" class="space-y-2">
                    <span>Entity type</span>
                    <select id="seo-entity-type" wire:model="entityType">
                        @foreach(\App\Services\Sites\AllowedSiteOverrideFields::ENTITY_TYPES as $entityTypeOption)
                            <option value="{{ $entityTypeOption }}">{{ str($entityTypeOption)->headline() }}</option>
                        @endforeach
                    </select>
                </label>
                <label for="seo-entity-id" class="space-y-2">
                    <span>Entity ID</span>
                    <input id="seo-entity-id" wire:model="entityId" type="number">
                </label>
                <label for="seo-locale" class="space-y-2">
                    <span>Locale <span aria-hidden="true">*</span><span class="sr-only">required</span></span>
                    <input id="seo-locale" wire:model="localeCode" placeholder="de-DE" required aria-required="true">
                </label>
                <label for="seo-meta-title" class="space-y-2">
                    <span>Meta title</span>
                    <input id="seo-meta-title" wire:model="metaTitle">
                </label>
                <label for="seo-meta-description" class="space-y-2">
                    <span>Meta description</span>
                    <textarea id="seo-meta-description" wire:model="metaDescription"></textarea>
                </label>
                <label for="seo-intro-text" class="space-y-2">
                    <span>Intro text</span>
                    <textarea id="seo-intro-text" wire:model="introText"></textarea>
                </label>
            </div>
        </x-admin.card>
        <div class="flex justify-end"><x-filament::button type="submit">Save local SEO</x-filament::button></div>
    </form>
</x-filament-panels::page>
