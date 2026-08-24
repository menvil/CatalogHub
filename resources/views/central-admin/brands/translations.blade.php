@extends('layouts.central-admin', ['activeNav' => 'Translations', 'pageTitle' => 'Brand Translations'])

@section('breadcrumbs')
    <a href="{{ route('filament.central.pages.home', absolute: false) }}" class="font-medium hover:text-admin-text">Central Admin</a><span aria-hidden="true">/</span>
    <a href="{{ route('central.translations.dashboard', absolute: false) }}" class="font-medium hover:text-admin-text">Translations</a><span aria-hidden="true">/</span>
    @can('catalog.products.manage')
        <a href="{{ route('central.brands.show', $brand, absolute: false) }}" class="font-medium hover:text-admin-text">{{ $brand->name }}</a>
    @else
        <span>{{ $brand->name }}</span>
    @endcan
    @if ($selectedLocale)
        <span aria-hidden="true">/</span><span aria-current="page">{{ $selectedLocale->code }}</span>
    @endif
@endsection

@section('content')
    <div class="space-y-admin-section" data-brand-translations-fixture="brand-translations-v1">
        <x-admin.page-header
            screen-id="CA-015"
            :show-screen-id="false"
            title="Brand Translations"
            description="Manage localized Brand content."
            :breadcrumbs="[]"
        >
            <x-slot:actions>
                <span class="max-w-full break-words text-sm font-semibold text-admin-text">{{ $brand->name }}</span>
            </x-slot:actions>
        </x-admin.page-header>

        @include('central-admin.brands.partials.subnav', ['active' => 'translations'])

        @if ($locales->isEmpty())
            <x-admin.empty-state
                title="No active locales are available for translation."
                description="Activate a locale before creating Brand translations."
            />
        @else
            <x-admin.card title="Locales" description="Choose an active locale to edit its independent Brand translation.">
                <nav class="flex gap-2 overflow-x-auto pb-1" aria-label="Translation locales">
                    @foreach ($locales as $locale)
                        @php
                            $localeTranslation = $translationsByLocale->get($locale->getKey());
                            $localeStatus = $localeTranslation?->status ?? \App\Enums\TranslationStatus::Missing;
                        @endphp
                        <a
                            href="{{ route('central.brands.translations.edit', [$brand, $locale->code], absolute: false) }}"
                            @if ($selectedLocale?->is($locale)) aria-current="page" @endif
                            @class([
                                'flex min-w-fit flex-col gap-1 rounded-admin-input border px-3 py-2 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary',
                                'border-admin-primary bg-admin-primary-soft text-admin-primary' => $selectedLocale?->is($locale),
                                'border-admin-border bg-admin-surface text-admin-text hover:bg-admin-surface-muted' => ! $selectedLocale?->is($locale),
                            ])
                        >
                            <span class="font-medium">{{ $locale->native_name ?: $locale->name }} ({{ $locale->code }})</span>
                            <x-admin.translation-status-badge :status="$localeStatus->value" :label="$localeStatus === \App\Enums\TranslationStatus::HumanReviewed ? 'Human reviewed' : null" class="self-start" />
                        </a>
                    @endforeach
                </nav>
            </x-admin.card>

            <div class="grid gap-admin-section xl:grid-cols-[minmax(0,1fr)_20rem]">
                <div class="min-w-0 space-y-admin-section">
                    <x-admin.card
                        :title="($selectedLocale->native_name ?: $selectedLocale->name).' ('.$selectedLocale->code.')'"
                        description="Localized text for this Brand and locale."
                    >
                        <div class="mb-admin-card flex flex-wrap items-center gap-3">
                            @php($selectedStatus = $translation?->status ?? \App\Enums\TranslationStatus::Missing)
                            <x-admin.translation-status-badge :status="$selectedStatus->value" :label="$selectedStatus === \App\Enums\TranslationStatus::HumanReviewed ? 'Human reviewed' : null" />
                            @if (! $translation)
                                <p class="text-sm text-admin-muted">No translation has been created for this locale yet.</p>
                            @elseif ($translation->status === \App\Enums\TranslationStatus::Outdated)
                                <p class="text-sm text-admin-muted">This translation is marked as outdated and should be reviewed.</p>
                            @endif
                        </div>

                        <x-ui.form.form-state
                            id="brand-translation-form"
                            :action="route('central.brands.translations.save', [$brand, $selectedLocale->code], absolute: false)"
                            method="post"
                            :leave-warning="false"
                            class="space-y-admin-card"
                        >
                            <x-ui.form.input
                                id="name"
                                name="name"
                                label="Localized name"
                                :value="old('name', $translation?->name ?? $brand->name)"
                                :error="$errors->first('name')"
                                :required="true"
                                :dir="$selectedLocale->direction"
                            />
                            <x-ui.form.input
                                id="tagline"
                                name="tagline"
                                label="Tagline"
                                :value="old('tagline', $translation?->tagline)"
                                :error="$errors->first('tagline')"
                                :optional="true"
                                :dir="$selectedLocale->direction"
                            />
                            <x-ui.form.textarea
                                id="short_description"
                                name="short_description"
                                label="Short description"
                                :value="old('short_description', $translation?->short_description)"
                                :error="$errors->first('short_description')"
                                :optional="true"
                                :rows="4"
                                :dir="$selectedLocale->direction"
                            />
                            <x-ui.form.textarea
                                id="description"
                                name="description"
                                label="Description"
                                :value="old('description', $translation?->description)"
                                :error="$errors->first('description')"
                                :optional="true"
                                :rows="9"
                                :dir="$selectedLocale->direction"
                            />
                            <x-ui.form.input
                                id="seo_title"
                                name="seo_title"
                                label="SEO title"
                                :value="old('seo_title', $translation?->seo_title)"
                                :error="$errors->first('seo_title')"
                                :optional="true"
                                :dir="$selectedLocale->direction"
                            />
                            <x-ui.form.textarea
                                id="seo_description"
                                name="seo_description"
                                label="SEO description"
                                :value="old('seo_description', $translation?->seo_description)"
                                :error="$errors->first('seo_description')"
                                :optional="true"
                                :rows="4"
                                :dir="$selectedLocale->direction"
                            />
                            <x-ui.form.select
                                id="status"
                                name="status"
                                label="Status"
                                :options="$statusOptions"
                                :selected="old('status', $translation?->status?->value ?? \App\Enums\TranslationStatus::HumanReviewed->value)"
                                :error="$errors->first('status')"
                                :required="true"
                            />
                            <div class="flex justify-end">
                                <x-ui.button type="submit">Save translation</x-ui.button>
                            </div>
                        </x-ui.form.form-state>
                    </x-admin.card>
                </div>

                <aside class="min-w-0 space-y-admin-section">
                    <x-admin.card title="Canonical Brand" description="Read-only source identity; these values are not submitted.">
                        <dl class="space-y-admin-card text-sm">
                            <div>
                                <dt class="font-medium text-admin-muted">Brand name</dt>
                                <dd class="mt-1 break-words text-admin-text">{{ $brand->name }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-admin-muted">Slug</dt>
                                <dd class="mt-1 break-all font-foundation-mono text-admin-text">{{ $brand->slug }}</dd>
                            </div>
                        </dl>
                    </x-admin.card>

                    @if ($translation?->status === \App\Enums\TranslationStatus::Approved)
                        <x-admin.card title="Approval">
                            <dl class="space-y-admin-card text-sm">
                                <div>
                                    <dt class="font-medium text-admin-muted">Approved at</dt>
                                    <dd class="mt-1 text-admin-text">
                                        @if ($translation->approved_at)
                                            <x-ui.timestamp :value="$translation->approved_at" timezone="UTC" />
                                        @else
                                            —
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-admin-muted">Approved by</dt>
                                    <dd class="mt-1 break-words text-admin-text">
                                        @if ($translation->approvedBy)
                                            {{ $translation->approvedBy->name }}<br>
                                            <span class="text-admin-muted">{{ $translation->approvedBy->email }}</span>
                                        @else
                                            —
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        </x-admin.card>
                    @endif
                </aside>
            </div>
        @endif
    </div>
@endsection
