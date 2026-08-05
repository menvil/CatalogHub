@extends('layouts.site-admin', [
    'acceptance' => $acceptance,
    'activeNav' => 'dashboard',
    'siteAdminAuthorizedSites' => $siteAdminAuthorizedSites,
    'siteAdminCurrentSite' => $siteAdminCurrentSite,
    'siteAdminNavigation' => $siteAdminNavigation,
    'siteAdminRuntimeContext' => $siteAdminRuntimeContext,
    'siteAdminShellPreviewState' => $siteAdminShellPreviewState,
    'siteAdminUser' => $siteAdminUser,
])

@section('pageTitle', 'Site Admin shell acceptance')

@section('content')
    <div
        class="space-y-admin-section"
        data-site-admin-shell-fixture="{{ $fixtureVersion }}"
        data-browser-acceptance="{{ $acceptance ? 'pending' : 'not-requested' }}"
    >
        <x-admin.page-header
            screen-id="SA-001"
            title="Site dashboard"
            description="Deterministic Site Admin acceptance fixture. It contains no catalog, sync, or metric data."
            status="Foundation"
            :breadcrumbs="[
                ['label' => 'Site Admin', 'url' => '/admin/site?site_id='.$siteAdminCurrentSite->getKey()],
                ['label' => 'Dashboard'],
            ]"
        />

        <x-site-admin.sync-status />

        <x-admin.empty-state
            title="Site Admin shell is ready"
            description="No site metrics are available in the foundation shell. The selected site remains visible throughout the request."
        />
    </div>
@endsection

@if ($acceptance)
    @push('head')
        <script>
            window.__siteAdminAcceptanceErrors = [];
            window.addEventListener('error', (event) => window.__siteAdminAcceptanceErrors.push(event.message));
            window.addEventListener('unhandledrejection', (event) => window.__siteAdminAcceptanceErrors.push(String(event.reason)));
        </script>
    @endpush

    @push('scripts')
        <script>
            const runSiteAdminAcceptance = () => {
                    const fixture = document.querySelector('[data-site-admin-shell-fixture]');
                    const shell = document.querySelector('[data-site-shell]');
                    const sidebar = document.querySelector('[data-site-sidebar]');
                    const open = document.querySelector('[data-site-sidebar-open]');
                    const collapse = document.querySelector('[data-site-sidebar-collapse]');
                    const selectorLink = document.querySelector('[data-site-selector-link]:not([aria-current])');
                    const failures = [];
                    const verify = (condition, message) => condition || failures.push(message);
                    const previewState = shell?.dataset.sitePreviewState ?? 'default';

                    verify(document.body.textContent.includes('Tech Germany'), 'current site remains visible');
                    verify(sidebar?.textContent.includes('Tech Germany'), 'mobile navigation identifies the current site');

                    if (previewState === 'mobile') {
                        selectorLink?.addEventListener('click', (event) => event.preventDefault(), { once: true });
                        selectorLink?.click();
                        verify(sidebar?.dataset.siteSidebarMobileOpen === 'false', 'site switch closes drawer');

                        document.body.classList.add('overflow-hidden');
                        open?.click();
                        verify(sidebar?.dataset.siteSidebarMobileOpen === 'true', 'mobile drawer opens');
                        verify(open?.getAttribute('aria-expanded') === 'true', 'mobile trigger exposes expanded state');
                        verify(document.body.classList.contains('site-sidebar-scroll-locked'), 'site sidebar owns its scroll lock');

                        const visibleFocusable = Array.from(sidebar?.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])') ?? [])
                            .filter((element) => element.getClientRects().length > 0);
                        const first = visibleFocusable.at(0);
                        const last = visibleFocusable.at(-1);
                        last?.focus();
                        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', bubbles: true }));
                        verify(document.activeElement === first, 'mobile focus trap wraps forward');

                        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
                        verify(sidebar?.dataset.siteSidebarMobileOpen === 'false', 'Escape closes drawer');
                        verify(open?.getAttribute('aria-expanded') === 'false', 'mobile trigger exposes collapsed state');
                        verify(!document.body.classList.contains('site-sidebar-scroll-locked'), 'site sidebar scroll lock releases');
                        verify(document.activeElement === open, 'focus returns to trigger');
                        verify(document.body.classList.contains('overflow-hidden'), 'other overlay scroll lock remains');
                        document.body.classList.remove('overflow-hidden');
                    } else {
                        collapse?.click();
                        verify(shell?.dataset.siteSidebarCollapsed === 'true', 'desktop collapse state');
                        verify(collapse?.getAttribute('aria-pressed') === 'true', 'desktop collapse semantics');
                        collapse?.click();
                        verify(shell?.dataset.siteSidebarCollapsed === 'false', 'desktop expand state');
                    }

                    verify(
                        window.__siteAdminAcceptanceErrors.length === 0,
                        'unexpected runtime error: ' + window.__siteAdminAcceptanceErrors.join(' | '),
                    );

                    fixture.dataset.browserAcceptance = failures.length === 0 ? 'passed' : 'failed';
                    fixture.dataset.browserAcceptanceFailures = failures.join(', ');
            };

            if (document.querySelector('[data-site-shell][data-site-shell-ready="true"]')) {
                runSiteAdminAcceptance();
            } else {
                document.addEventListener('site-admin-shell:ready', runSiteAdminAcceptance, { once: true });
            }
        </script>
    @endpush
@endif
