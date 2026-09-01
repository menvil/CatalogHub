@php
    $editing = $mode === 'edit';
    $name = old('name', $editing ? $brand?->name : null);
    $slug = old('slug', $editing ? $brand?->slug : null);
    $websiteUrl = old('website_url', $editing ? $brand?->website_url : null);
    $countryId = old('country_id', $editing ? $brand?->country_id : null);
    $foundedYear = old('founded_year', $editing ? $brand?->founded_year : null);
    $supportUrl = old('support_url', $editing ? $brand?->support_url : null);
    $contactEmail = old('contact_email', $editing ? $brand?->contact_email : null);
    $primaryColor = old('primary_color', $editing ? $brand?->primary_color : null);
@endphp

<x-ui.form.form-state
    id="brand-form"
    :action="$action"
    :method="$method"
    :leave-warning="false"
    class="min-w-0"
    data-screen-region="profile-editor"
>
    <div class="grid min-w-0 gap-admin-section lg:grid-cols-[minmax(0,1fr)_minmax(17rem,21rem)]">
        <main class="min-w-0 space-y-admin-section lg:order-1" data-screen-region="profile-fields">
            <x-admin.card
                title="General Information"
                description="Canonical identity and origin details for this Brand."
                data-screen-region="general-information"
            >
                <div class="grid gap-admin-card md:grid-cols-2">
                    <x-ui.form.input
                        id="brand-name"
                        name="name"
                        label="Name"
                        :value="$name"
                        :error="$errors->first('name')"
                        help="Canonical Brand name used across the catalog."
                        autocomplete="organization"
                        maxlength="255"
                        required
                        :autofocus="! $editing"
                    />

                    <x-ui.form.slug-input
                        id="brand-slug"
                        name="slug"
                        label="Slug"
                        :value="$slug"
                        :error="$errors->first('slug')"
                        :help="$editing
                            ? 'Stable canonical identifier; change it only when necessary.'
                            : 'Leave blank to generate it from the Brand name.'"
                        maxlength="255"
                        autocomplete="off"
                    />

                    <x-ui.form.searchable-select
                        id="brand-country"
                        name="country_id"
                        label="Country"
                        :options="$countryOptions"
                        :selected="$countryId"
                        :error="$errors->first('country_id')"
                        placeholder="Select a Country"
                        search-placeholder="Search by name or code"
                        help="Search by Country name or code."
                        clearable
                    />

                    <x-ui.form.input
                        id="brand-founded-year"
                        name="founded_year"
                        type="number"
                        label="Founded year"
                        :value="$foundedYear"
                        :error="$errors->first('founded_year')"
                        placeholder="1938"
                        min="1000"
                        :max="\App\Support\Validation\CentralBrandProfileConstraints::maximumFoundedYear()"
                        inputmode="numeric"
                        optional
                    />
                </div>
            </x-admin.card>

            @if ($editing)
                @php($currentOwner = $brand->ownership?->organization)
                <x-admin.card
                    title="Ownership / Parent Company"
                    description="The current direct legal or corporate owner of this Brand."
                    data-screen-region="parent-company"
                >
                    @if ($currentOwner !== null)
                        <div class="rounded-admin-input border border-admin-border bg-admin-surface-muted p-admin-card">
                            <p class="text-xs font-medium uppercase tracking-wide text-admin-muted">Current Parent Company</p>
                            <p class="mt-2 break-words text-base font-semibold text-admin-text" data-current-parent-company>{{ $currentOwner->name }}</p>
                            <p class="mt-1 text-sm text-admin-muted">Canonical Organization #{{ $currentOwner->getKey() }}</p>
                        </div>
                    @else
                        <div class="rounded-admin-input border border-dashed border-admin-border bg-admin-surface-muted p-admin-card text-center" data-parent-company-empty>
                            <p class="font-medium text-admin-text">No Parent Company assigned</p>
                            <p class="mt-1 text-sm text-admin-muted">This Brand currently has no direct corporate owner relation.</p>
                        </div>
                    @endif

                    <div class="mt-admin-card flex flex-wrap gap-admin-field">
                        <x-ui.button
                            type="button"
                            variant="secondary"
                            aria-haspopup="dialog"
                            aria-controls="manage-parent-company-modal"
                            data-admin-modal-open-target="manage-parent-company-modal"
                        >{{ $currentOwner === null ? 'Assign existing Organization' : 'Change Parent Company' }}</x-ui.button>
                        <x-ui.button
                            type="button"
                            variant="secondary"
                            aria-haspopup="dialog"
                            aria-controls="create-parent-company-modal"
                            data-admin-modal-open-target="create-parent-company-modal"
                        >Create new Organization</x-ui.button>
                        @if ($currentOwner !== null)
                            <x-ui.button
                                type="button"
                                variant="danger"
                                aria-haspopup="dialog"
                                aria-controls="clear-parent-company-modal"
                                data-admin-modal-open-target="clear-parent-company-modal"
                            >Clear Parent Company</x-ui.button>
                        @endif
                    </div>
                </x-admin.card>
            @endif

            <x-admin.card
                title="Online Presence"
                description="Official destinations and public Brand contact details."
                data-screen-region="online-presence"
            >
                <div class="grid gap-admin-card md:grid-cols-2">
                    <x-ui.form.input
                        id="brand-website"
                        name="website_url"
                        type="url"
                        label="Website"
                        :value="$websiteUrl"
                        :error="$errors->first('website_url')"
                        placeholder="https://www.example.com/"
                        autocomplete="url"
                        maxlength="255"
                        help="Official primary Brand website."
                        optional
                    />

                    <x-ui.form.input
                        id="brand-support-url"
                        name="support_url"
                        type="url"
                        label="Support URL"
                        :value="$supportUrl"
                        :error="$errors->first('support_url')"
                        placeholder="https://www.example.com/support/"
                        autocomplete="url"
                        maxlength="255"
                        help="Official customer or product support page."
                        optional
                    />

                    <div class="md:col-span-2">
                        <x-ui.form.input
                            id="brand-contact-email"
                            name="contact_email"
                            type="email"
                            label="Contact email"
                            :value="$contactEmail"
                            :error="$errors->first('contact_email')"
                            placeholder="support@example.com"
                            autocomplete="email"
                            maxlength="254"
                            help="Public Brand contact or support address."
                            optional
                        />
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card
                title="Brand Identity"
                description="A simple canonical visual identity value."
                data-screen-region="brand-identity-fields"
            >
                <div class="max-w-md">
                    <x-ui.form.color-input
                        id="brand-primary-color"
                        name="primary_color"
                        label="Primary color"
                        :value="$primaryColor"
                        :error="$errors->first('primary_color')"
                        help="Use a six-digit hexadecimal value such as #1428A0."
                        optional
                    />
                </div>
            </x-admin.card>
        </main>

        <aside class="order-first min-w-0 space-y-admin-section lg:order-2" data-screen-region="profile-sidebar">
            <x-admin.card title="Brand Status" data-screen-region="status-context">
                <div class="flex items-center justify-between gap-admin-field">
                    <span class="text-sm font-medium text-admin-muted">Lifecycle</span>
                    @if ($editing)
                        @php($statusVariant = $brand->status->color() === 'gray' ? 'neutral' : $brand->status->color())
                        <x-admin.status-badge :label="$brand->status->label()" :variant="$statusVariant" />
                    @else
                        <x-admin.status-badge label="Draft" variant="neutral" />
                    @endif
                </div>
                <p class="mt-3 text-sm text-admin-muted">
                    {{ $editing
                        ? 'Lifecycle changes are managed from Brand Overview.'
                        : 'New Brands are created as Draft and can be activated after review.' }}
                </p>
            </x-admin.card>

            @if ($editing)
                <x-admin.card title="Brand Identity" data-screen-region="logo-context">
                    @if ($logo->url)
                        <div class="flex h-28 items-center justify-center rounded-admin-card border border-admin-border bg-admin-surface-muted p-4 sm:h-40 sm:p-5">
                            <img src="{{ $logo->url }}" alt="{{ $brand->name }} logo" class="max-h-full max-w-full object-contain">
                        </div>
                    @else
                        <div class="rounded-admin-card border border-dashed border-admin-border bg-admin-surface-muted px-4 py-8 text-center">
                            <p class="text-sm font-medium text-admin-text">No logo assigned</p>
                            <p class="mt-1 text-xs text-admin-muted">Add the canonical logo in Brand Media.</p>
                        </div>
                    @endif

                    @can('catalog.brands.manage')
                        <x-ui.button
                            variant="secondary"
                            class="mt-admin-card w-full"
                            :href="route('central.brands.media', $brand, absolute: false)"
                        >Manage Media</x-ui.button>
                    @endcan
                </x-admin.card>
            @endif
        </aside>
    </div>

    <div class="sticky bottom-0 z-10 mt-admin-section rounded-admin-card border border-admin-border bg-admin-surface/95 p-admin-card shadow-admin-floating backdrop-blur" data-screen-region="form-actions">
        <div class="flex items-center justify-between gap-admin-field">
            <x-ui.button variant="secondary" :href="$cancelUrl" data-brand-form-cancel>Cancel</x-ui.button>
            <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
        </div>
    </div>
</x-ui.form.form-state>
