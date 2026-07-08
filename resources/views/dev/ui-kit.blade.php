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
        </section>
    </main>
@endsection
