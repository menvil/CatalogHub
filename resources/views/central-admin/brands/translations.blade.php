@extends('layouts.central-admin', ['activeNav' => 'Translations', 'pageTitle' => 'Brand Translations'])

@section('breadcrumbs')
    <a href="{{ route('filament.central.pages.home', absolute: false) }}" class="font-medium hover:text-admin-text">Central Admin</a><span aria-hidden="true">/</span>
    <a href="{{ route('central.translations.dashboard', absolute: false) }}" class="font-medium hover:text-admin-text">Translations</a><span aria-hidden="true">/</span>
    @can('catalog.brands.manage')
        <a href="{{ route('central.brands.show', $brand, absolute: false) }}" class="font-medium hover:text-admin-text">{{ $brand->name }}</a>
    @else
        <span>{{ $brand->name }}</span>
    @endcan
    @if ($selectedLocale)
        <span aria-hidden="true">/</span><span aria-current="page">{{ $selectedLocale->code }}</span>
    @endif
@endsection

@section('content')
    @php
        $selectedStatus = $translation?->status ?? \App\Enums\TranslationStatus::Missing;
        $canApprove = $translation?->status === \App\Enums\TranslationStatus::HumanReviewed && $sourceHashMatches;
        $canMarkOutdated = $translation !== null && $translation->status !== \App\Enums\TranslationStatus::Outdated;
        $statusExplanation = match ($selectedStatus) {
            \App\Enums\TranslationStatus::Missing => 'No translation row exists for this active locale. Nothing is persisted until Save.',
            \App\Enums\TranslationStatus::MachineTranslated => 'The row carries the shared machine-translated state. CA-015 does not run a translation provider.',
            \App\Enums\TranslationStatus::HumanReviewed => 'A human-reviewed target is saved and may be explicitly approved when its source hash is current.',
            \App\Enums\TranslationStatus::Approved => 'Approval was explicit and is attributed below. Editing localized copy invalidates this approval.',
            \App\Enums\TranslationStatus::Outdated => $sourceHashMatches
                ? 'The row was explicitly marked outdated; its saved text is preserved.'
                : 'The stored source hash differs from the current canonical Brand name or slug.',
        };
    @endphp

    <div class="space-y-admin-section" data-brand-translations-fixture="brand-translations-v2">
        <x-admin.page-header
            screen-id="CA-015"
            :show-screen-id="false"
            :title="$brand->name"
            description="Translate canonical Brand copy for active locales."
            :breadcrumbs="[]"
        >
            @if ($selectedLocale)
                <x-slot:actions>
                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                        <span class="break-words text-sm font-semibold text-admin-text">{{ $selectedLocale->native_name ?: $selectedLocale->name }}</span>
                        <span class="font-foundation-mono text-xs text-admin-muted">{{ $selectedLocale->code }}</span>
                        <x-admin.translation-status-badge :status="$selectedStatus->value" />
                    </div>
                </x-slot:actions>
            @endif
        </x-admin.page-header>

        @include('central-admin.brands.partials.subnav', ['active' => 'translations'])

        @if ($locales->isEmpty())
            <x-admin.empty-state
                title="No active locales are available for translation."
                description="Activate a locale before creating Brand translations. Existing rows for inactive locales are retained but are not editable here."
            />
        @else
            <x-admin.card title="Active locales" description="Choose a target locale. Opening a locale never creates a translation row.">
                <nav class="flex max-w-full gap-2 overflow-x-auto pb-1" aria-label="Translation locales">
                    @foreach ($locales as $locale)
                        @php
                            $localeTranslation = $translationsByLocale->get($locale->getKey());
                            $localeStatus = $localeTranslation?->status ?? \App\Enums\TranslationStatus::Missing;
                        @endphp
                        <a
                            href="{{ route('central.brands.translations.edit', [$brand, $locale->code], absolute: false) }}"
                            @if ($selectedLocale?->is($locale)) aria-current="page" @endif
                            @class([
                                'flex min-w-40 shrink-0 flex-col gap-1 rounded-admin-input border px-3 py-2 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary',
                                'border-admin-primary bg-admin-primary-soft text-admin-primary' => $selectedLocale?->is($locale),
                                'border-admin-border bg-admin-surface text-admin-text hover:bg-admin-surface-muted' => ! $selectedLocale?->is($locale),
                            ])
                        >
                            <span class="font-medium">{{ $locale->native_name ?: $locale->name }}</span>
                            <span class="font-foundation-mono text-xs opacity-80">{{ $locale->code }}</span>
                            <x-admin.translation-status-badge :status="$localeStatus->value" class="mt-1 self-start" />
                        </a>
                    @endforeach
                </nav>
            </x-admin.card>

            @if ($errors->has('translation'))
                <div class="rounded-admin-input border border-admin-danger/30 bg-admin-danger-soft px-4 py-3 text-sm text-admin-text" role="alert">
                    {{ $errors->first('translation') }}
                </div>
            @endif

            <x-admin.card data-screen-region="translation-workflow">
                <div class="flex flex-col gap-admin-card lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-admin.translation-status-badge :status="$selectedStatus->value" />
                            <span class="font-foundation-mono text-xs text-admin-muted">Target {{ $selectedLocale->code }}</span>
                        </div>
                        <p class="mt-2 max-w-3xl text-sm text-admin-muted">{{ $statusExplanation }}</p>
                    </div>

                    <div class="flex flex-wrap gap-admin-field lg:justify-end">
                        <form method="POST" action="{{ route('central.brands.translations.outdated', [$brand, $selectedLocale->code], absolute: false) }}">
                            @csrf
                            <x-ui.button type="submit" variant="secondary" :disabled="! $canMarkOutdated">Mark outdated</x-ui.button>
                        </form>
                        <form method="POST" action="{{ route('central.brands.translations.approve', [$brand, $selectedLocale->code], absolute: false) }}">
                            @csrf
                            <x-ui.button type="submit" variant="secondary" :disabled="! $canApprove">Approve translation</x-ui.button>
                        </form>
                        <x-ui.button type="submit" form="brand-translation-form">Save translation</x-ui.button>
                    </div>
                </div>
            </x-admin.card>

            <div class="grid min-w-0 gap-admin-section xl:grid-cols-[16rem_minmax(0,1fr)_18rem] xl:items-start" data-screen-region="source-target-workspace">
                <aside class="min-w-0 space-y-admin-section" aria-label="Source context">
                    <x-admin.card title="Source context" description="Canonical values used by the existing Brand source-hash contract.">
                        <dl class="space-y-admin-card text-sm">
                            <div>
                                <dt class="font-medium text-admin-muted">Canonical name</dt>
                                <dd class="mt-1 break-words text-admin-text">{{ $brand->name }}</dd>
                                <x-ui.button
                                    variant="secondary"
                                    class="mt-3 w-full"
                                    data-brand-translation-copy-source
                                    data-brand-translation-copy-target="name"
                                    :data-brand-translation-source-value="$brand->name"
                                >Copy canonical name</x-ui.button>
                                <p class="mt-2 text-xs text-admin-muted">Client-side only. Save remains a separate action and Copy never changes status.</p>
                            </div>
                            <div class="border-t border-admin-border pt-admin-card">
                                <dt class="font-medium text-admin-muted">Canonical slug</dt>
                                <dd class="mt-1 break-all font-foundation-mono text-admin-text">{{ $brand->slug }}</dd>
                                <p class="mt-2 text-xs text-admin-muted">Slug participates in the source hash but has no localized target in CA-015.</p>
                            </div>
                            <div class="border-t border-admin-border pt-admin-card">
                                <dt class="font-medium text-admin-muted">Stored source state</dt>
                                <dd class="mt-2">
                                    @if (! $translation)
                                        <span class="text-admin-muted">Not stored</span>
                                    @elseif ($sourceHashMatches)
                                        <span class="font-medium text-admin-success">Matches current source</span>
                                    @else
                                        <span class="font-medium text-admin-outdated">Canonical source differs</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-admin-card rounded-admin-input border border-dashed border-admin-border bg-admin-surface-muted p-3">
                            <p class="text-sm font-medium text-admin-text">No invented source copy</p>
                            <p class="mt-1 text-xs text-admin-muted">Tagline, descriptions, and SEO fields have no canonical Brand equivalent, so CA-015 does not fabricate values for them.</p>
                        </div>
                    </x-admin.card>
                </aside>

                <main class="min-w-0">
                    <x-admin.card
                        :title="'Target translation · '.($selectedLocale->native_name ?: $selectedLocale->name)"
                        :description="'Localized fields are stored only in BrandTranslation for '.$selectedLocale->code.'.'"
                    >
                        <x-ui.form.form-state
                            id="brand-translation-form"
                            :action="route('central.brands.translations.save', [$brand, $selectedLocale->code], absolute: false)"
                            method="post"
                            :leave-warning="false"
                            class="space-y-admin-card"
                        >
                            <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                                <x-ui.form.input
                                    id="name"
                                    name="name"
                                    label="Localized name"
                                    :value="old('name', $translation?->name)"
                                    :error="$errors->first('name')"
                                    help="Required target. Canonical name is the only field with a real Copy from Source value."
                                    :required="true"
                                    :dir="$selectedLocale->direction"
                                    maxlength="255"
                                />
                                <x-ui.button
                                    variant="secondary"
                                    class="sm:mb-[1.625rem]"
                                    data-brand-translation-copy-source
                                    data-brand-translation-copy-target="name"
                                    :data-brand-translation-source-value="$brand->name"
                                >Copy from Source</x-ui.button>
                            </div>
                            <x-ui.form.input
                                id="tagline"
                                name="tagline"
                                label="Tagline"
                                :value="old('tagline', $translation?->tagline)"
                                :error="$errors->first('tagline')"
                                help="No authoritative canonical source value."
                                :optional="true"
                                :dir="$selectedLocale->direction"
                                maxlength="255"
                            />
                            <x-ui.form.textarea
                                id="short_description"
                                name="short_description"
                                label="Short description"
                                :value="old('short_description', $translation?->short_description)"
                                :error="$errors->first('short_description')"
                                help="No authoritative canonical source value."
                                :optional="true"
                                :rows="4"
                                :dir="$selectedLocale->direction"
                                maxlength="1000"
                            />
                            <x-ui.form.textarea
                                id="description"
                                name="description"
                                label="Description"
                                :value="old('description', $translation?->description)"
                                :error="$errors->first('description')"
                                help="No authoritative canonical source value."
                                :optional="true"
                                :rows="9"
                                :dir="$selectedLocale->direction"
                                maxlength="10000"
                            />
                            <x-ui.form.input
                                id="seo_title"
                                name="seo_title"
                                label="SEO title"
                                :value="old('seo_title', $translation?->seo_title)"
                                :error="$errors->first('seo_title')"
                                help="Stored centrally but not published by CA-015. No canonical source value."
                                :optional="true"
                                :dir="$selectedLocale->direction"
                                maxlength="255"
                            />
                            <x-ui.form.textarea
                                id="seo_description"
                                name="seo_description"
                                label="SEO description"
                                :value="old('seo_description', $translation?->seo_description)"
                                :error="$errors->first('seo_description')"
                                help="Stored centrally but not published by CA-015. No canonical source value."
                                :optional="true"
                                :rows="4"
                                :dir="$selectedLocale->direction"
                                maxlength="500"
                            />
                            <x-ui.form.select
                                id="status"
                                name="status"
                                label="Review state"
                                :options="$statusOptions"
                                :selected="old('status', $translation?->status?->value ?? \App\Enums\TranslationStatus::HumanReviewed->value)"
                                :error="$errors->first('status')"
                                help="Approval is never granted by Save. Changed approved copy returns to Human reviewed and clears stale attribution."
                                :required="true"
                            />
                            <div class="flex justify-end border-t border-admin-border pt-admin-card">
                                <x-ui.button type="submit">Save translation</x-ui.button>
                            </div>
                        </x-ui.form.form-state>
                    </x-admin.card>
                </main>

                <aside class="min-w-0 space-y-admin-section" aria-label="Translation metadata and activity">
                    <x-admin.card title="Workflow status">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-admin.translation-status-badge :status="$selectedStatus->value" />
                            @if ($translation)
                                <span class="font-foundation-mono text-xs text-admin-muted">Row #{{ $translation->getKey() }}</span>
                            @endif
                        </div>
                        <p class="mt-3 text-sm text-admin-muted">{{ $statusExplanation }}</p>

                        @if ($translation && ! $canApprove && $translation->status !== \App\Enums\TranslationStatus::Approved)
                            <p class="mt-3 rounded-admin-input bg-admin-surface-muted p-3 text-xs text-admin-muted">
                                @if ($translation->status !== \App\Enums\TranslationStatus::HumanReviewed)
                                    Save as Human reviewed before approval.
                                @elseif (! $sourceHashMatches)
                                    Save against the current source before approval.
                                @endif
                            </p>
                        @endif
                    </x-admin.card>

                    @if ($translation?->status === \App\Enums\TranslationStatus::Approved)
                        <x-admin.card title="Approval" data-screen-region="approval-metadata">
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

                    <x-admin.card title="Recent activity" description="Latest events for this Brand and locale." data-screen-region="translation-activity">
                        @if ($activity->isEmpty())
                            <p class="text-sm text-admin-muted">No translation activity has been recorded for this locale.</p>
                        @else
                            <ol class="divide-y divide-admin-border">
                                @foreach ($activity as $event)
                                    @php
                                        $activityLabel = match ($event->action) {
                                            \App\Enums\AuditAction::CatalogBrandTranslationSaved->value => $event->before_json === null ? 'Translation created' : 'Translation saved',
                                            \App\Enums\AuditAction::TranslationApproved->value => 'Translation approved',
                                            \App\Enums\AuditAction::TranslationMarkedOutdated->value => 'Marked outdated',
                                            default => 'Translation changed',
                                        };
                                        $changedFields = collect($event->after_json['changed_fields'] ?? [])
                                            ->filter(fn (mixed $field): bool => is_string($field))
                                            ->map(fn (string $field): string => $field === 'source_context' ? 'canonical source' : (string) str($field)->replace('_', ' '))
                                            ->implode(', ');
                                    @endphp
                                    <li class="py-3 first:pt-0 last:pb-0">
                                        <p class="text-sm font-medium text-admin-text">{{ $activityLabel }}</p>
                                        <p class="mt-1 text-xs text-admin-muted">
                                            {{ $event->actor?->name ?? 'System' }} · <x-ui.timestamp :value="$event->created_at" timezone="UTC" />
                                        </p>
                                        @if ($changedFields !== '')
                                            <p class="mt-1 break-words text-xs text-admin-muted">Changed: {{ $changedFields }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </x-admin.card>
                </aside>
            </div>
        @endif
    </div>
@endsection
