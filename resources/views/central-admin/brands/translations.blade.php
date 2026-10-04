@extends('layouts.central-admin', ['activeNav' => 'Brands', 'pageTitle' => 'Brand Translations'])

@section('breadcrumbs')
    @can('catalog.brands.manage')
        <a href="{{ route('central.brands.index', absolute: false) }}" class="font-medium hover:text-admin-text">Brands</a><span aria-hidden="true">/</span>
        <a href="{{ route('central.brands.show', $brand, absolute: false) }}" class="font-medium hover:text-admin-text">{{ $brand->name }}</a>
    @else
        <span>Brands</span><span aria-hidden="true">/</span><span>{{ $brand->name }}</span>
    @endcan
    <span aria-hidden="true">/</span><span aria-current="page">Translations</span>
@endsection

@section('content')
    @php
        $selectedStatus = $translation?->status ?? \App\Enums\TranslationStatus::Missing;
        $sourceStatus = $sourceTranslation?->status ?? \App\Enums\TranslationStatus::Missing;
        $canManage = auth()->user()?->can('translations.manage') === true
            && auth()->user()?->can('central.mutation.execute') === true;
        $canApprove = $translation?->status === \App\Enums\TranslationStatus::HumanReviewed && $sourceHashMatches;
        $canMarkOutdated = $translation !== null && $translation->status !== \App\Enums\TranslationStatus::Outdated;
        $sourceParameters = $sourceLocale ? ['source' => $sourceLocale->code] : [];
        $swapTarget = $sourceLocale ?? $sourceLocales->first();
        $statusExplanation = match ($selectedStatus) {
            \App\Enums\TranslationStatus::Missing => 'No saved translation yet. Save to create one.',
            \App\Enums\TranslationStatus::MachineTranslated => 'Review this translation before approval.',
            \App\Enums\TranslationStatus::HumanReviewed => 'Ready for explicit approval once the canonical source is current.',
            \App\Enums\TranslationStatus::Approved => 'Approved. Editing content requires a new approval.',
            \App\Enums\TranslationStatus::Outdated => $sourceHashMatches
                ? 'Marked outdated. Review and save before approval.'
                : 'Canonical Brand context changed. Review and save before approval.',
        };
        $fields = [
            ['name', 'Localized name', 255, 1],
            ['tagline', 'Tagline', 255, 1],
            ['short_description', 'Short description', 1000, 3],
            ['description', 'Description', 10000, 5],
            ['seo_title', 'SEO title', 255, 1],
            ['seo_description', 'SEO description', 500, 3],
        ];
    @endphp

    <div class="brand-translation-page" data-brand-translations-fixture="brand-translations-v3">
        <x-admin.page-header class="brand-translation-heading" screen-id="CA-015" :show-screen-id="false" title="Brand Translations" description="Manage localized Brand content across active locales." :breadcrumbs="[]">
            <x-slot:actions>
                <span class="text-xs text-admin-muted">Brand: {{ $brand->name }} · <span class="font-foundation-mono">{{ $brand->slug }}</span></span>
                @can('catalog.brands.manage')<x-ui.button :href="route('central.brands.show', $brand, absolute: false)" variant="secondary">View Brand</x-ui.button>@endcan
            </x-slot:actions>
        </x-admin.page-header>
        @include('central-admin.brands.partials.subnav', ['active' => 'translations'])

        @if ($locales->isEmpty())
            <x-admin.empty-state title="No active locales are available for translation." description="Activate a locale to manage translations." />
        @else
            <section class="brand-translation-direction" aria-label="Translation direction" data-screen-region="translation-direction">
                <p class="brand-translation-mobile-direction" aria-label="{{ $sourceLocale?->name ?? 'Choose source' }} to {{ $selectedLocale->name }}">{{ strtoupper($sourceLocale?->language_code ?? '—') }} → {{ strtoupper($selectedLocale->language_code) }}</p>
                <div class="min-w-0">
                    <label for="source-language" class="mb-2 block text-xs font-medium text-admin-muted">Source language</label>
                    <div class="brand-translation-select">
                        <select id="source-language" class="brand-translation-source-select" data-brand-translation-source-selector data-brand-translation-language-selector aria-describedby="language-selection-help" @disabled($sourceLocales->isEmpty())>
                            <option value="" data-language-url="{{ route('central.brands.translations.edit', [$brand, $selectedLocale->code], absolute: false) }}" @selected(! $sourceLocale)>Choose source language</option>
                            @foreach ($sourceLocales as $candidate)
                                <option value="{{ $candidate->code }}" data-language-url="{{ route('central.brands.translations.edit', [$brand, $selectedLocale->code, 'source' => $candidate->code], absolute: false) }}" @selected($sourceLocale?->is($candidate))>{{ $candidate->name }} · {{ $candidate->code }} · {{ \App\Enums\TranslationStatus::options()[($translationsByLocale->get($candidate->getKey())?->status ?? \App\Enums\TranslationStatus::Missing)->value] }}</option>
                            @endforeach
                            @if ($swapTarget)
                                <optgroup label="Switch direction">
                                    <option value="{{ $selectedLocale->code }}" data-language-url="{{ route('central.brands.translations.edit', [$brand, $swapTarget->code, 'source' => $selectedLocale->code], absolute: false) }}">{{ $selectedLocale->name }} · {{ $selectedLocale->code }} · {{ \App\Enums\TranslationStatus::options()[$selectedStatus->value] }}</option>
                                </optgroup>
                            @endif
                        </select>
                        <x-ui.icon name="chevron-down" decorative size="sm" data-select-chevron />
                    </div>
                </div>
                <span class="brand-translation-arrow" aria-hidden="true">→</span>
                <div class="min-w-0">
                    <label for="target-language" class="mb-2 block text-xs font-medium text-admin-muted">Target language</label>
                    <div class="brand-translation-select">
                        <select id="target-language" class="brand-translation-source-select" data-brand-translation-target-selector data-brand-translation-language-selector aria-describedby="language-selection-help">
                            @foreach ($locales as $candidate)
                                @php
                                    $candidateStatus = $translationsByLocale->get($candidate->getKey())?->status ?? \App\Enums\TranslationStatus::Missing;
                                    $nextSource = $sourceLocale?->is($candidate) ? $selectedLocale : $sourceLocale;
                                @endphp
                                <option value="{{ $candidate->code }}" data-language-url="{{ route('central.brands.translations.edit', [$brand, $candidate->code, ...($nextSource ? ['source' => $nextSource->code] : [])], absolute: false) }}" @selected($selectedLocale->is($candidate))>{{ $candidate->name }} · {{ $candidate->code }} · {{ \App\Enums\TranslationStatus::options()[$candidateStatus->value] }}</option>
                            @endforeach
                        </select>
                        <x-ui.icon name="chevron-down" decorative size="sm" data-select-chevron />
                    </div>
                </div>
                <p id="language-selection-help" class="brand-translation-language-help">Selecting a language already on the other side swaps the direction.</p>
                <noscript class="brand-translation-language-help">
                    <details>
                        <summary>Choose languages</summary>
                        <nav aria-label="Source languages" class="mt-2 flex flex-col gap-2">
                            @foreach ($sourceLocales as $candidate)
                                <a href="{{ route('central.brands.translations.edit', [$brand, $selectedLocale->code, 'source' => $candidate->code], absolute: false) }}">Source: {{ $candidate->name }} · {{ $candidate->code }}</a>
                            @endforeach
                            @if ($swapTarget)<a href="{{ route('central.brands.translations.edit', [$brand, $swapTarget->code, 'source' => $selectedLocale->code], absolute: false) }}">Source: {{ $selectedLocale->name }} · {{ $selectedLocale->code }} — Switch direction</a>@endif
                        </nav>
                        <nav aria-label="Target languages" class="mt-3 flex flex-col gap-2">
                            @foreach ($locales as $candidate)
                                @php
                                    $nextSource = $sourceLocale?->is($candidate) ? $selectedLocale : $sourceLocale;
                                @endphp
                                <a href="{{ route('central.brands.translations.edit', [$brand, $candidate->code, ...($nextSource ? ['source' => $nextSource->code] : [])], absolute: false) }}">Target: {{ $candidate->name }} · {{ $candidate->code }}</a>
                            @endforeach
                        </nav>
                    </details>
                </noscript>
            </section>

            @if ($errors->has('translation') || $errors->has('source'))
                <p class="rounded-admin-input bg-admin-danger-soft p-3 text-sm" role="alert">{{ $errors->first('translation') ?: $errors->first('source') }}</p>
            @endif
            @if (! $sourceLocale)
                <p class="rounded-admin-input bg-admin-surface-muted p-3 text-sm" role="status">Choose another source language for reference.</p>
            @elseif (! $sourceTranslation)
                <p class="rounded-admin-input bg-admin-warning-soft p-3 text-sm" role="status">No source translation available for {{ $sourceLocale->name }} ({{ $sourceLocale->code }}). Choose another source language.</p>
            @elseif ($sourceStatus === \App\Enums\TranslationStatus::Outdated)
                <p class="rounded-admin-input bg-admin-warning-soft p-3 text-sm" role="status">You are translating from an outdated source.</p>
            @elseif ($sourceStatus === \App\Enums\TranslationStatus::Missing)
                <p class="rounded-admin-input bg-admin-warning-soft p-3 text-sm" role="status">The source translation is marked Missing. Choose another source language if needed.</p>
            @endif

            <div class="brand-translation-workspace" data-screen-region="source-target-workspace">
                <section class="brand-translation-editor" aria-label="Translation editor">
                    <header class="brand-translation-editor-heading">
                        <div>
                            <h2 class="text-base font-semibold">{{ $sourceLocale ? $sourceLocale->name.' ('.$sourceLocale->code.')' : 'Choose source' }} → {{ $selectedLocale->name }} ({{ $selectedLocale->code }})</h2>
                            <p class="mt-1 text-xs text-admin-muted">Compare each field, then save your translation.</p>
                        </div>
                        @if ($canManage)
                            <div class="flex flex-wrap gap-2">
                                @if ($sourceTranslation)<x-ui.button variant="secondary" data-brand-translation-copy-all>Copy all from Source</x-ui.button>@endif
                                <x-ui.button type="submit" form="brand-translation-form">Save translation</x-ui.button>
                            </div>
                        @endif
                    </header>
                    <x-ui.form.form-state id="brand-translation-form" :action="route('central.brands.translations.save', [$brand, $selectedLocale->code, ...$sourceParameters], absolute: false)" method="post" :leave-warning="false">
                        <div class="brand-translation-table-heading" aria-hidden="true">
                            <span>Field</span><span>Source · {{ $sourceLocale?->name ?? 'Not selected' }}</span><span>Target · {{ $selectedLocale->name }}</span>
                        </div>
                        @foreach ($fields as [$field, $label, $limit, $rows])
                            @php
                                $targetValue = old($field, $translation?->getAttribute($field)) ?? '';
                                $sourceValue = $sourceTranslation?->getAttribute($field);
                                $canonicalFallback = $field === 'name' && $sourceTranslation && blank($sourceValue);
                                if ($canonicalFallback) $sourceValue = $brand->name;
                            @endphp
                            <div class="brand-translation-field" data-translation-field="{{ $field }}">
                                <div class="brand-translation-field-label">
                                    <label for="{{ $field }}" class="text-sm font-medium">{{ $label }}</label> @if ($field === 'name')<span aria-hidden="true" class="text-admin-danger">*</span>@endif
                                    <span class="mt-1 block text-xs text-admin-muted" data-brand-translation-counter="{{ $field }}">{{ mb_strlen($targetValue) }} / {{ $limit }}</span>
                                    @if ($canManage && filled($sourceValue))
                                        <button type="button" class="brand-translation-copy" aria-label="Copy source for {{ $label }}" data-brand-translation-copy-source data-brand-translation-copy-target="{{ $field }}" data-brand-translation-source-value="{{ $sourceValue }}">Copy source</button>
                                    @endif
                                </div>
                                <div class="brand-translation-source">
                                    <p class="brand-translation-mobile-label">Source · {{ $sourceLocale?->name ?? 'Not selected' }}</p>
                                    @if ($canonicalFallback)<p class="mb-1 text-xs font-medium text-admin-muted">Canonical fallback</p>@endif
                                    <div class="brand-translation-source-value" dir="{{ $canonicalFallback ? 'auto' : ($sourceLocale?->direction ?? 'ltr') }}" @if ($sourceLocale && ! $canonicalFallback) lang="{{ $sourceLocale->code }}" @endif data-source-field="{{ $field }}">{{ filled($sourceValue) ? $sourceValue : 'No source value' }}</div>
                                </div>
                                <div class="brand-translation-target">
                                    <p class="brand-translation-mobile-label">Target · {{ $selectedLocale->name }}</p>
                                    @if ($rows === 1)
                                        <input id="{{ $field }}" name="{{ $field }}" type="text" value="{{ $targetValue }}" class="brand-translation-control" dir="{{ $selectedLocale->direction }}" lang="{{ $selectedLocale->code }}" maxlength="{{ $limit }}" @required($field === 'name') @readonly(! $canManage) @if ($errors->has($field)) aria-invalid="true" aria-describedby="{{ $field }}-error" @endif>
                                    @else
                                        <textarea id="{{ $field }}" name="{{ $field }}" rows="{{ $rows }}" class="brand-translation-control" dir="{{ $selectedLocale->direction }}" lang="{{ $selectedLocale->code }}" maxlength="{{ $limit }}" @readonly(! $canManage) @if ($errors->has($field)) aria-invalid="true" aria-describedby="{{ $field }}-error" @endif>{{ $targetValue }}</textarea>
                                    @endif
                                    @if ($errors->has($field))<p id="{{ $field }}-error" class="mt-1 text-xs text-admin-danger" role="alert">{{ $errors->first($field) }}</p>@endif

                                </div>
                            </div>
                        @endforeach
                        @if ($canManage)
                            <div class="brand-translation-save">
                                @if ($selectedStatus === \App\Enums\TranslationStatus::Approved)
                                    <input type="hidden" name="status" value="approved">
                                @else
                                    <div>
                                        <label for="status" class="mr-2 text-xs font-medium text-admin-muted">Save as</label>
                                        <div class="brand-translation-select inline-block align-middle">
                                            <select id="status" name="status" class="brand-translation-source-select !w-auto" @if ($errors->has('status')) aria-invalid="true" aria-describedby="status-error" @endif>
                                                <option value="human_reviewed" @selected(old('status', $selectedStatus->value) !== 'machine_translated')>Human reviewed</option>
                                                <option value="machine_translated" @selected(old('status', $selectedStatus->value) === 'machine_translated')>Machine translated</option>
                                            </select>
                                            <x-ui.icon name="chevron-down" decorative size="sm" data-select-chevron />
                                        </div>
                                        @if ($errors->has('status'))<p id="status-error" class="mt-1 text-xs text-admin-danger" role="alert">{{ $errors->first('status') }}</p>@endif
                                    </div>
                                @endif
                                <span class="text-xs text-admin-muted">Save records your review. Approval is a separate action.</span>
                                <x-ui.button type="submit">Save translation</x-ui.button>
                            </div>
                        @endif
                    </x-ui.form.form-state>
                </section>
                <aside class="min-w-0 space-y-admin-section" aria-label="Translation metadata and activity">
                    <x-admin.card title="Workflow status">
                        <p class="mb-2 text-sm font-medium">{{ $selectedLocale->name }} · {{ $selectedLocale->code }}</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-admin.translation-status-badge :status="$selectedStatus->value" />
                            @if ($translation)<span class="font-foundation-mono text-xs text-admin-muted">Row #{{ $translation->getKey() }}</span>@endif
                        </div>
                        <p class="mt-3 text-sm text-admin-muted">{{ $statusExplanation }}</p>
                        @if ($translation)
                            <p class="mt-2 text-xs text-admin-muted">Canonical context: {{ $sourceHashMatches ? 'Current' : 'Changed' }}</p>
                        @endif
                        @if ($canManage)
                            <div class="mt-4 flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('central.brands.translations.approve', [$brand, $selectedLocale->code, ...$sourceParameters], absolute: false) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="secondary" :disabled="! $canApprove" :aria-describedby="! $canApprove ? 'approval-help' : null">Approve translation</x-ui.button>
                                </form>
                                <form method="POST" action="{{ route('central.brands.translations.outdated', [$brand, $selectedLocale->code, ...$sourceParameters], absolute: false) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="secondary" :disabled="! $canMarkOutdated">Mark outdated</x-ui.button>
                                </form>
                            </div>
                            @if (! $canApprove)
                                <p id="approval-help" class="mt-3 text-xs text-admin-muted">{{ $selectedStatus === \App\Enums\TranslationStatus::Approved ? 'Already approved.' : 'Save against the current source before approval.' }}</p>
                            @endif
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
