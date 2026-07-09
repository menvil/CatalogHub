@extends('layouts.central-admin')

@section('pageTitle', 'Admin visual smoke')

@section('breadcrumbs')
    <span>Dev</span>
    <span>/</span>
    <span>Visual smoke</span>
@endsection

@section('pageActions')
    <a href="{{ route('dev.ui-kit') }}" class="rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm font-medium text-admin-muted">
        UI Kit
    </a>
@endsection

@section('content')
    <div class="space-y-admin-section">
        <x-admin.card title="Dashboard composition preview" description="KPI cards, status badges and warnings in a dashboard-like layout.">
            <div class="grid gap-admin-field md:grid-cols-3">
                <div class="rounded-admin-input bg-admin-surface-muted p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-admin-muted">Catalog quality</p>
                    <p class="mt-2 text-3xl font-semibold text-admin-text">92%</p>
                    <x-admin.status-badge label="Healthy" variant="success" size="sm" class="mt-3" />
                </div>
                <div class="rounded-admin-input bg-admin-surface-muted p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-admin-muted">Warnings</p>
                    <p class="mt-2 text-3xl font-semibold text-admin-text">17</p>
                    <x-admin.quality-warning-badge label="Needs review" level="medium" count="17" class="mt-3" />
                </div>
                <div class="rounded-admin-input bg-admin-surface-muted p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-admin-muted">Translations</p>
                    <p class="mt-2 text-3xl font-semibold text-admin-text">4</p>
                    <x-admin.translation-status-badge status="outdated" locale="de" class="mt-3" />
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Form composition preview" description="Product detail form shell using localized fields, unit inputs and media picker.">
            <div class="grid gap-admin-field xl:grid-cols-[minmax(0,2fr)_minmax(22rem,1fr)]">
                <div class="space-y-admin-field">
                    <x-admin.localized-field-editor
                        field-name="Product title"
                        :locales="['en', 'bg']"
                        :values="['en' => 'Coffee machine', 'bg' => '']"
                        :statuses="['en' => 'approved', 'bg' => 'missing']"
                    />
                    <x-admin.unit-value-input label="Power" value="100" unit="w" :available-units="['w', 'kw']" canonical-preview="100 W" />
                </div>
                <x-admin.media-picker empty-title="No media assigned" empty-description="Media workflows are connected in Phase 8." />
            </div>
        </x-admin.card>

        <x-admin.card title="Workflow composition preview" description="Import progress and conflict/change review shells in a workflow-like view.">
            <div class="space-y-admin-field">
                <x-admin.import-progress-panel
                    source-name="Vendor feed"
                    status="running"
                    :steps="[
                        ['key' => 'raw', 'label' => 'Raw import', 'status' => 'completed'],
                        ['key' => 'mapping', 'label' => 'Mapping', 'status' => 'current'],
                        ['key' => 'review', 'label' => 'Review', 'status' => 'pending'],
                    ]"
                    :stats="[
                        ['label' => 'raw products', 'value' => 120],
                        ['label' => 'needs review', 'value' => 18],
                        ['label' => 'errors', 'value' => 4],
                    ]"
                />
                <div class="grid gap-admin-field lg:grid-cols-2">
                    <x-admin.conflict-review-card title="Spec conflict" entity-label="Demo product" field-label="power" source-a="Feed" source-b="Central" value-a="90 W" value-b="100 W" severity="medium" />
                    <x-admin.change-request-card request-title="Correct title" requester-label="Site editor" entity-label="Demo product" field-label="title" current-value="Old title" proposed-value="New title" status="pending" />
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Table/action composition preview" description="Dense row actions and status badges for future tables.">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[42rem] text-left text-sm">
                    <thead class="text-xs uppercase tracking-wide text-admin-muted">
                        <tr>
                            <th class="border-b border-admin-border px-3 py-2">Item</th>
                            <th class="border-b border-admin-border px-3 py-2">Projection</th>
                            <th class="border-b border-admin-border px-3 py-2">Quality</th>
                            <th class="border-b border-admin-border px-3 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border-b border-admin-border px-3 py-3 text-admin-text">Demo product</td>
                            <td class="border-b border-admin-border px-3 py-3"><x-admin.projection-status-badge status="stale" /></td>
                            <td class="border-b border-admin-border px-3 py-3"><x-admin.quality-warning-badge label="Missing attrs" level="medium" count="3" /></td>
                            <td class="border-b border-admin-border px-3 py-3">
                                <button type="button" disabled class="rounded-admin-input border border-admin-border px-3 py-2 text-admin-muted">Review</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    </div>
@endsection
