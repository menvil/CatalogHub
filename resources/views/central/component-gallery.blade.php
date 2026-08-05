@extends('layouts.central-admin', ['activeNav' => 'component-gallery', 'centralUser' => $centralUser])

@section('pageTitle', 'Foundation Component Gallery')

@section('content')
    <div class="space-y-foundation-section" data-gallery-fixture="{{ $fixtureVersion }}">
        <x-admin.page-header
            screen-id="CA-DS-001"
            title="Foundation Component Gallery"
            description="Deterministic reference for semantic tokens and shared presentation primitives. It contains no catalog or tenant data."
            status="Foundation"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('filament.central.pages.home', absolute: false)],
                ['label' => 'Design system'],
            ]"
            class="rounded-foundation-card border border-foundation-border bg-foundation-surface p-foundation-card shadow-foundation-card"
        />

        <section class="rounded-foundation-card border border-foundation-border bg-foundation-surface p-foundation-card shadow-foundation-card">
            <h3 class="text-foundation-title font-semibold text-foundation-text">Color and status tokens</h3>
            <div class="mt-foundation-card grid gap-foundation-field foundation-tablet:grid-cols-2 foundation-desktop:grid-cols-4">
                @foreach ([
                    ['Canvas', 'bg-foundation-canvas'],
                    ['Surface', 'bg-foundation-surface'],
                    ['Accent', 'bg-foundation-accent'],
                    ['Muted surface', 'bg-foundation-surface-muted'],
                    ['Success', 'bg-foundation-success'],
                    ['Warning', 'bg-foundation-warning'],
                    ['Danger', 'bg-foundation-danger'],
                    ['Info', 'bg-foundation-info'],
                ] as [$label, $class])
                    <div class="rounded-foundation-control border border-foundation-border bg-foundation-surface p-foundation-field">
                        <div class="h-12 rounded-foundation-control border border-foundation-border {{ $class }}"></div>
                        <p class="mt-foundation-compact text-foundation-label font-medium text-foundation-text">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-foundation-card flex flex-wrap gap-foundation-field">
                @foreach ($statuses as $variant => $label)
                    <x-admin.status-badge :variant="$variant" :label="$label" />
                @endforeach
            </div>
        </section>

        <section class="grid gap-foundation-section foundation-desktop:grid-cols-2">
            <div class="rounded-foundation-card border border-foundation-border bg-foundation-surface p-foundation-card shadow-foundation-card">
                <h3 class="text-foundation-title font-semibold text-foundation-text">Typography</h3>
                <div class="mt-foundation-card space-y-foundation-field">
                    <p class="text-foundation-display font-semibold text-foundation-text">Display</p>
                    <p class="text-foundation-heading font-semibold text-foundation-text">Heading</p>
                    <p class="text-foundation-title font-medium text-foundation-text">Title</p>
                    <p class="text-foundation-body text-foundation-text">Body copy remains readable across administrative contexts.</p>
                    <p class="text-foundation-label font-medium text-foundation-text-muted">Label and metadata</p>
                    <code class="font-foundation-mono text-foundation-code text-foundation-accent">foundation.token.reference</code>
                </div>
            </div>

            <div class="rounded-foundation-card border border-foundation-border bg-foundation-surface p-foundation-card shadow-foundation-card">
                <h3 class="text-foundation-title font-semibold text-foundation-text">Spacing and geometry</h3>
                <div class="mt-foundation-card space-y-foundation-field">
                    @foreach ([
                        ['compact', 'w-foundation-compact'],
                        ['field', 'w-foundation-field'],
                        ['card', 'w-foundation-card'],
                        ['section', 'w-foundation-section'],
                        ['page', 'w-foundation-page'],
                    ] as [$label, $class])
                        <div class="flex items-center gap-foundation-field">
                            <span class="w-20 text-foundation-caption text-foundation-text-muted">{{ $label }}</span>
                            <span class="h-3 rounded-foundation-pill bg-foundation-accent {{ $class }}"></span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-foundation-card grid grid-cols-3 gap-foundation-field">
                    <div class="h-20 rounded-foundation-control border border-foundation-border bg-foundation-surface-muted"></div>
                    <div class="h-20 rounded-foundation-card border border-foundation-border bg-foundation-surface-muted shadow-foundation-card"></div>
                    <div class="h-20 rounded-foundation-modal border border-foundation-border bg-foundation-surface-muted shadow-foundation-floating"></div>
                </div>
            </div>
        </section>

        <section class="rounded-foundation-card border border-foundation-border bg-foundation-surface p-foundation-card shadow-foundation-card">
            <h3 class="text-foundation-title font-semibold text-foundation-text">Heroicons</h3>
            <div class="mt-foundation-card grid gap-foundation-field foundation-tablet:grid-cols-2 foundation-desktop:grid-cols-3">
                @foreach ($icons as $semantic => $icon)
                    <div class="flex items-center gap-foundation-field rounded-foundation-control border border-foundation-border p-foundation-field">
                        <x-ui.icon :name="$icon['icon']" :label="$icon['meaning']" class="text-foundation-accent" />
                        <div>
                            <p class="text-foundation-label font-medium text-foundation-text">{{ ucfirst($semantic) }}</p>
                            <p class="text-foundation-caption text-foundation-text-muted">{{ $icon['meaning'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-foundation-card border border-foundation-border bg-foundation-surface p-foundation-card shadow-foundation-card">
            <h3 class="text-foundation-title font-semibold text-foundation-text">Responsive density</h3>
            <div class="mt-foundation-card overflow-x-auto">
                <table class="w-full min-w-foundation-table text-left text-foundation-label">
                    <thead class="text-foundation-caption uppercase text-foundation-text-muted">
                        <tr>
                            <th class="px-foundation-field py-foundation-compact">Viewport</th>
                            <th class="px-foundation-field py-foundation-compact">Fixed width</th>
                            <th class="px-foundation-field py-foundation-compact">Density</th>
                            <th class="px-foundation-field py-foundation-compact">Foundation behavior</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-foundation-border">
                        @foreach ($viewports as $name => $viewport)
                            <tr>
                                <td class="px-foundation-field py-foundation-compact font-medium text-foundation-text">{{ ucfirst($name) }}</td>
                                <td class="px-foundation-field py-foundation-compact text-foundation-text-muted">{{ $viewport['width'] }} × {{ $viewport['height'] }} px</td>
                                <td class="px-foundation-field py-foundation-compact text-foundation-text-muted">{{ ucfirst($viewport['density']) }}</td>
                                <td class="px-foundation-field py-foundation-compact text-foundation-text-muted">{{ $viewport['behavior'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
