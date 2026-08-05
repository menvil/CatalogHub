@extends('layouts.central-admin', [
    'activeNav' => 'dashboard',
    'centralUser' => $centralUser,
    'centralShellPreviewState' => $centralShellPreviewState,
])

@section('pageTitle', 'Central shell acceptance')

@section('content')
    <div
        class="space-y-admin-section"
        data-central-shell-fixture="{{ $fixtureVersion }}"
        data-browser-acceptance="{{ $acceptance ? 'pending' : 'not-requested' }}"
    >
        <x-admin.page-header
            screen-id="CA-001"
            :title="$centralShellPreviewState === 'long-header'
                ? 'Central administration foundation shell with a deliberately long deterministic acceptance heading'
                : 'Central dashboard'"
            description="Deterministic Central shell acceptance fixture. It contains no catalog, tenant, or metric data."
            status="Foundation"
            :breadcrumbs="[['label' => 'Dashboard']]"
        />

        <x-admin.empty-state
            title="Central Admin shell is ready"
            description="No metrics are available in the foundation shell. Available sections can be opened from the navigation."
        />
    </div>
@endsection

@if ($acceptance)
    @push('head')
        <script>
            window.__centralAcceptanceErrors = [];
            window.addEventListener('error', (event) => window.__centralAcceptanceErrors.push(event.message));
            window.addEventListener('unhandledrejection', (event) => window.__centralAcceptanceErrors.push(String(event.reason)));
        </script>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                window.setTimeout(() => {
                    const fixture = document.querySelector('[data-central-shell-fixture]');
                    const shell = document.querySelector('[data-central-shell]');
                    const sidebar = document.querySelector('[data-central-sidebar]');
                    const open = document.querySelector('[data-central-sidebar-open]');
                    const collapse = document.querySelector('[data-central-sidebar-collapse]');
                    const failures = [];
                    const verify = (condition, message) => condition || failures.push(message);

                    collapse?.click();
                    verify(shell?.dataset.centralSidebarCollapsed === 'true', 'desktop collapse state');
                    verify(collapse?.getAttribute('aria-pressed') === 'true', 'desktop collapse semantics');
                    collapse?.click();

                    document.body.classList.add('overflow-hidden');
                    open?.click();
                    verify(sidebar?.dataset.centralSidebarMobileOpen === 'true', 'mobile drawer opens');
                    verify(document.body.classList.contains('central-sidebar-mobile-open'), 'sidebar scroll lock');

                    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
                    verify(sidebar?.dataset.centralSidebarMobileOpen === 'false', 'Escape closes drawer');
                    verify(document.activeElement === open, 'focus returns to trigger');
                    verify(document.body.classList.contains('overflow-hidden'), 'other overlay scroll lock remains');
                    document.body.classList.remove('overflow-hidden');
                    verify(window.__centralAcceptanceErrors.length === 0, 'no browser runtime errors');

                    fixture.dataset.browserAcceptance = failures.length === 0 ? 'passed' : 'failed';
                    fixture.dataset.browserAcceptanceFailures = failures.join(', ');
                }, 100);
            });
        </script>
    @endpush
@endif
