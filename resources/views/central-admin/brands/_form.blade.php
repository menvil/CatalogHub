@php
    $editing = $mode === 'edit';
    $name = old('name', $editing ? $brand?->name : null);
    $slug = old('slug', $editing ? $brand?->slug : null);
    $websiteUrl = old('website_url', $editing ? $brand?->website_url : null);
    $countryCode = old('country_code', $editing ? $brand?->country_code : null);
@endphp

<x-ui.form.form-state
    id="brand-form"
    :action="$action"
    :method="$method"
    class="min-w-0"
    data-screen-region="form-fields"
>
    <x-admin.card title="General">
        <div class="space-y-admin-card">
            <x-ui.form.input
                id="brand-name"
                name="name"
                label="Name"
                :value="$name"
                :error="$errors->first('name')"
                help="Canonical brand name used across the central catalog."
                autocomplete="organization"
                maxlength="255"
                required
                autofocus
            />

            <x-ui.form.slug-input
                id="brand-slug"
                name="slug"
                label="Slug"
                :value="$slug"
                :error="$errors->first('slug')"
                :help="$editing
                    ? 'Changing the slug changes the canonical identifier. Existing integrations may depend on it.'
                    : 'Leave blank to generate from the brand name.'"
                maxlength="255"
                autocomplete="off"
            />

            <x-ui.form.input
                id="brand-website"
                name="website_url"
                type="url"
                label="Website"
                :value="$websiteUrl"
                :error="$errors->first('website_url')"
                placeholder="https://www.example.com"
                autocomplete="url"
                maxlength="255"
                optional
            />

            <x-ui.form.input
                id="brand-country-code"
                name="country_code"
                label="Country code"
                :value="$countryCode"
                :error="$errors->first('country_code')"
                help="Two-letter country code, for example KR, JP or US."
                autocomplete="country"
                maxlength="2"
                inputmode="text"
                optional
            />

            @unless ($editing)
                <p class="rounded-admin-input border border-admin-info/30 bg-admin-info-soft px-3 py-2 text-sm text-admin-text" data-screen-region="status-context">
                    New brands are created as Draft.
                </p>
            @endunless
        </div>

        <x-slot:footer>
            <div class="flex items-center justify-between gap-admin-field" data-screen-region="form-actions">
                <x-ui.button variant="secondary" :href="$cancelUrl" data-brand-form-cancel>Cancel</x-ui.button>
                <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
            </div>
        </x-slot:footer>
    </x-admin.card>
</x-ui.form.form-state>
