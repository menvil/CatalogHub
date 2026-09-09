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
    <div class="grid min-w-0 gap-admin-section xl:grid-cols-[minmax(0,1fr)_17rem] xl:items-start">
        <main class="order-2 min-w-0 xl:order-1 xl:col-start-1 xl:row-start-1" data-screen-region="profile-fields">
            <x-admin.card
                title="Brand information"
                description="Canonical, language-neutral details used across the catalog."
                data-screen-region="brand-information"
            >
                <div class="space-y-5">
                    <section class="min-w-0" data-screen-region="identity-fields">
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-admin-text">Identity</h3>
                            <p class="mt-1 text-xs text-admin-muted">Core identity and market of origin.</p>
                        </div>

                        <div class="grid min-w-0 gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <x-ui.form.input
                                id="brand-name"
                                name="name"
                                label="Name"
                                :value="$name"
                                :error="$errors->first('name')"
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
                                :help="$editing ? 'Stable catalog identifier.' : 'Optional; generated from the name.'"
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
                                clearable
                            />
                        </div>
                    </section>

                    <section class="min-w-0 border-t border-admin-border pt-5" data-screen-region="company-origin">
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-admin-text">Company &amp; origin</h3>
                            <p class="mt-1 text-xs text-admin-muted">Corporate ownership and founding year.</p>
                        </div>

                        @if ($editing)
                            @php($currentOwner = $brand->ownership?->organization)
                            <div class="grid min-w-0 gap-4 md:grid-cols-[minmax(0,2fr)_minmax(10rem,1fr)] md:items-start">
                                <div class="min-w-0 space-y-1.5" data-screen-region="parent-company">
                                    <p class="text-sm font-medium text-admin-text">Parent Company</p>
                                    <div class="brand-parent-company-control flex min-w-0 items-center justify-between gap-2 rounded-admin-input border border-admin-border bg-admin-surface-muted">
                                        <div class="min-w-0">
                                            @if ($currentOwner !== null)
                                                <p class="min-w-0 break-words text-sm text-admin-text" data-current-parent-company><span class="font-semibold">{{ $currentOwner->name }}</span><span class="whitespace-nowrap text-xs text-admin-muted" data-current-parent-company-reference> · Organization #{{ $currentOwner->getKey() }}</span></p>
                                            @else
                                                <p class="text-sm font-medium text-admin-muted" data-parent-company-empty>No parent company</p>
                                            @endif
                                        </div>

                                        <div class="flex shrink-0 items-center gap-2">
                                            <x-ui.button
                                                type="button"
                                                variant="secondary"
                                                aria-haspopup="dialog"
                                                aria-controls="manage-parent-company-modal"
                                                data-admin-modal-open-target="manage-parent-company-modal"
                                            >{{ $currentOwner === null ? 'Assign' : 'Change' }}</x-ui.button>

                                            <details class="admin-row-actions-menu" data-admin-row-actions-menu>
                                                <summary aria-label="More Parent Company actions" aria-haspopup="menu"><span aria-hidden="true">⋮</span></summary>
                                                <div role="menu" data-admin-row-actions-panel>
                                                    <button
                                                        type="button"
                                                        role="menuitem"
                                                        class="text-sm font-semibold text-admin-primary"
                                                        aria-haspopup="dialog"
                                                        aria-controls="create-parent-company-modal"
                                                        data-admin-modal-open-target="create-parent-company-modal"
                                                    >Create Organization</button>
                                                    @if ($currentOwner !== null)
                                                        <button
                                                            type="button"
                                                            role="menuitem"
                                                            class="text-sm font-semibold text-admin-danger"
                                                            aria-haspopup="dialog"
                                                            aria-controls="clear-parent-company-modal"
                                                            data-admin-modal-open-target="clear-parent-company-modal"
                                                        >Clear Parent Company</button>
                                                    @endif
                                                </div>
                                            </details>
                                        </div>
                                    </div>
                                </div>
                        @else
                            <div class="max-w-xs">
                        @endif

                            <x-ui.form.input
                                id="brand-founded-year"
                                name="founded_year"
                                type="number"
                                label="Founded year"
                                :value="$foundedYear"
                                :error="$errors->first('founded_year')"
                                placeholder="1976"
                                min="1000"
                                :max="\App\Support\Validation\CentralBrandProfileConstraints::maximumFoundedYear()"
                                inputmode="numeric"
                                optional
                            />
                        </div>
                    </section>

                    <section class="min-w-0 border-t border-admin-border pt-5" data-screen-region="online-presence">
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-admin-text">Online presence</h3>
                        </div>

                        <div class="grid min-w-0 gap-4 md:grid-cols-2">
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
                                optional
                            />

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
                                optional
                            />

                            <x-ui.form.color-input
                                id="brand-primary-color"
                                name="primary_color"
                                label="Primary color"
                                :value="$primaryColor"
                                :error="$errors->first('primary_color')"
                                optional
                            />
                        </div>
                    </section>
                </div>
            </x-admin.card>
        </main>

        <aside class="contents xl:order-2 xl:col-start-2 xl:row-start-1 xl:block xl:space-y-admin-section xl:sticky xl:top-6" data-screen-region="profile-sidebar">
            <div class="order-1 min-w-0" data-screen-region="status-context">
                <x-admin.card title="Brand status" padding="sm" class="brand-status-card">
                    <div class="flex items-center justify-between gap-admin-field">
                        <span class="text-sm font-medium text-admin-muted">Lifecycle</span>
                        @if ($editing)
                            @php($statusVariant = $brand->status->color() === 'gray' ? 'neutral' : $brand->status->color())
                            <x-admin.status-badge :label="$brand->status->label()" :variant="$statusVariant" />
                        @else
                            <x-admin.status-badge label="Draft" variant="neutral" />
                        @endif
                    </div>
                    <p class="mt-2 text-xs leading-4 text-admin-muted">
                        {{ $editing
                            ? 'Lifecycle changes are managed from Brand Overview.'
                            : 'New Brands are created as Draft.' }}
                    </p>
                </x-admin.card>
            </div>

            @if ($editing)
                <div class="order-3 min-w-0" data-screen-region="logo-context">
                    <x-admin.card title="Brand identity" padding="sm">
                        @if ($logo->url)
                            <div class="flex h-36 items-center justify-center rounded-admin-card border border-admin-border bg-admin-surface-muted p-4">
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
                                class="mt-3 w-full"
                                :href="route('central.brands.media', $brand, absolute: false)"
                            >Manage media</x-ui.button>
                        @endcan
                    </x-admin.card>
                </div>
            @endif
        </aside>
    </div>
</x-ui.form.form-state>
