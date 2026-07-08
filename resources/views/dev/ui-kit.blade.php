@extends('layouts.app')

@section('content')
    <main class="min-h-screen bg-admin-background p-admin-page text-admin-text">
        <section class="mx-auto max-w-5xl space-y-admin-section">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-admin-primary">CatalogHub Admin UI Kit</p>
                <h1 class="mt-2 text-3xl font-semibold text-admin-text">Admin design tokens</h1>
                <p class="mt-2 max-w-2xl text-sm text-admin-muted">
                    Phase 2 token preview for future Central Admin and Site Admin interfaces.
                </p>
            </div>

            <section class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card shadow-admin-card">
                <h2 class="text-lg font-semibold text-admin-text">Colors</h2>

                <div class="mt-4 grid gap-admin-field sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        ['name' => 'Background', 'class' => 'bg-admin-background'],
                        ['name' => 'Surface', 'class' => 'bg-admin-surface'],
                        ['name' => 'Muted surface', 'class' => 'bg-admin-surface-muted'],
                        ['name' => 'Primary', 'class' => 'bg-admin-primary'],
                        ['name' => 'Success', 'class' => 'bg-admin-success'],
                        ['name' => 'Warning', 'class' => 'bg-admin-warning'],
                        ['name' => 'Danger', 'class' => 'bg-admin-danger'],
                        ['name' => 'Info', 'class' => 'bg-admin-info'],
                    ] as $token)
                        <div class="rounded-admin-input border border-admin-border bg-admin-surface p-3">
                            <div class="{{ $token['class'] }} h-10 rounded-admin-input border border-admin-border"></div>
                            <p class="mt-2 text-sm font-medium text-admin-text">{{ $token['name'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card shadow-admin-card">
                <h2 class="text-lg font-semibold text-admin-text">Surface example</h2>

                <div class="mt-4 rounded-admin-card border border-admin-border bg-admin-surface-muted p-admin-card">
                    <div class="flex flex-wrap items-center gap-admin-field">
                        <span class="rounded-admin-badge bg-admin-primary-soft px-3 py-1 text-sm font-medium text-admin-primary">
                            Primary
                        </span>
                        <span class="rounded-admin-badge bg-admin-success-soft px-3 py-1 text-sm font-medium text-admin-success">
                            Success
                        </span>
                        <span class="rounded-admin-badge bg-admin-warning-soft px-3 py-1 text-sm font-medium text-admin-warning">
                            Warning
                        </span>
                        <span class="rounded-admin-badge bg-admin-danger-soft px-3 py-1 text-sm font-medium text-admin-danger">
                            Danger
                        </span>
                    </div>
                </div>
            </section>

            <section class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card shadow-admin-card">
                <h2 class="text-lg font-semibold text-admin-text">Status badges</h2>

                <div class="mt-4 flex flex-wrap items-center gap-admin-field">
                    <x-admin.status-badge label="Completed" variant="success" />
                    <x-admin.status-badge label="Needs Review" variant="warning" />
                    <x-admin.status-badge label="Failed" variant="danger" />
                    <x-admin.status-badge label="Queued" variant="info" />
                    <x-admin.status-badge label="Draft" variant="neutral" />
                    <x-admin.status-badge label="Small" variant="success" size="sm" />
                </div>
            </section>

            <section class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card shadow-admin-card">
                <h2 class="text-lg font-semibold text-admin-text">Quality warning badges</h2>

                <div class="mt-4 flex flex-wrap items-center gap-admin-field">
                    <x-admin.quality-warning-badge label="Optional metadata missing" level="low" count="3" />
                    <x-admin.quality-warning-badge label="Required attributes missing" level="medium" count="12" />
                    <x-admin.quality-warning-badge label="Mapping conflicts" level="high" count="5" />
                    <x-admin.quality-warning-badge label="Media failures" level="critical" count="2" />
                </div>
            </section>

            <section class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card shadow-admin-card">
                <h2 class="text-lg font-semibold text-admin-text">Translation status badges</h2>

                <div class="mt-4 flex flex-wrap items-center gap-admin-field">
                    <x-admin.translation-status-badge status="missing" locale="en" />
                    <x-admin.translation-status-badge status="machine" locale="de" />
                    <x-admin.translation-status-badge status="reviewed" locale="fr" />
                    <x-admin.translation-status-badge status="approved" locale="bg" />
                    <x-admin.translation-status-badge status="outdated" locale="es" />
                </div>
            </section>

            <section class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card shadow-admin-card">
                <h2 class="text-lg font-semibold text-admin-text">Projection status badges</h2>

                <div class="mt-4 flex flex-wrap items-center gap-admin-field">
                    <x-admin.projection-status-badge status="synced" last-updated="2m ago" />
                    <x-admin.projection-status-badge status="stale" last-updated="1d ago" />
                    <x-admin.projection-status-badge status="syncing" />
                    <x-admin.projection-status-badge status="failed" />
                    <x-admin.projection-status-badge status="missing" />
                </div>
            </section>

            <section class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card shadow-admin-card">
                <h2 class="text-lg font-semibold text-admin-text">Empty states</h2>

                <div class="mt-4 grid gap-admin-field lg:grid-cols-2">
                    <x-admin.empty-state
                        title="No imports yet"
                        description="Import workflows will connect to this shell in a later phase."
                        icon="0"
                    >
                        <x-slot:action>
                            <button type="button" disabled class="rounded-admin-input bg-admin-primary px-3 py-2 text-sm font-medium text-white opacity-60">
                                Import placeholder
                            </button>
                        </x-slot:action>
                    </x-admin.empty-state>

                    <x-admin.empty-state
                        title="No stale projections"
                        description="Projection checks can reuse the warning variant without reading projection tables."
                        icon="!"
                        variant="warning"
                    />
                </div>
            </section>

            <section class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card shadow-admin-card">
                <h2 class="text-lg font-semibold text-admin-text">Admin cards</h2>

                <div class="mt-4 grid gap-admin-field lg:grid-cols-2">
                    <x-admin.card title="Catalog quality" description="Reusable card shell for dashboard and form sections.">
                        <x-slot:actions>
                            <button type="button" disabled class="rounded-admin-input border border-admin-border px-3 py-2 text-sm font-medium text-admin-muted">
                                Action
                            </button>
                        </x-slot:actions>

                        <p class="text-sm text-admin-muted">Card content uses the shared admin surface, radius and shadow tokens.</p>
                    </x-admin.card>

                    <x-admin.card title="Danger section" description="Used for destructive or high-risk admin actions." variant="danger">
                        <p class="text-sm text-admin-danger">This is a shell only; action behavior is wired later.</p>
                    </x-admin.card>
                </div>
            </section>

            <section class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card shadow-admin-card">
                <h2 class="text-lg font-semibold text-admin-text">Admin tabs</h2>

                <div class="mt-4">
                    <x-admin.tabs
                        active="specs"
                        :items="[
                            ['key' => 'overview', 'label' => 'Overview'],
                            ['key' => 'specs', 'label' => 'Specs', 'count' => 18],
                            ['key' => 'media', 'label' => 'Media', 'count' => 6],
                            ['key' => 'translations', 'label' => 'Translations', 'count' => 4],
                        ]"
                    />
                </div>
            </section>

            <section class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card shadow-admin-card">
                <h2 class="text-lg font-semibold text-admin-text">Drawer</h2>

                <div class="relative mt-4 h-64 overflow-hidden rounded-admin-card border border-admin-border bg-admin-surface-muted">
                    <x-admin.drawer title="Change request detail" size="sm" :backdrop="false" :contained="true">
                        <p class="text-sm text-admin-muted">
                            Side panels can host previews, review details and workflow actions without depending on domain models.
                        </p>

                        <x-slot:footer>
                            <button type="button" disabled class="rounded-admin-input bg-admin-primary px-3 py-2 text-sm font-medium text-white opacity-60">
                                Placeholder action
                            </button>
                        </x-slot:footer>
                    </x-admin.drawer>
                </div>
            </section>

            <section class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card shadow-admin-card">
                <h2 class="text-lg font-semibold text-admin-text">Confirmation modal</h2>

                <div class="relative mt-4 h-72 overflow-hidden rounded-admin-card border border-admin-border bg-admin-surface-muted">
                    <x-admin.confirmation-modal
                        title="Reject import draft"
                        message="This confirms the review decision for the selected draft shell."
                        confirm-label="Reject draft"
                        cancel-label="Keep reviewing"
                        variant="danger"
                        :contained="true"
                    />
                </div>
            </section>

            <section class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card shadow-admin-card">
                <h2 class="text-lg font-semibold text-admin-text">Stepper wizard</h2>

                <div class="mt-4">
                    <x-admin.stepper-wizard
                        current-step="normalization"
                        :steps="[
                            ['key' => 'raw', 'label' => 'Raw import', 'status' => 'completed'],
                            ['key' => 'mapping', 'label' => 'Mapping', 'status' => 'completed'],
                            ['key' => 'normalization', 'label' => 'Normalization', 'status' => 'current'],
                            ['key' => 'review', 'label' => 'Review', 'status' => 'pending'],
                            ['key' => 'publish', 'label' => 'Publish', 'status' => 'pending'],
                        ]"
                    />
                </div>
            </section>
        </section>
    </main>
@endsection
