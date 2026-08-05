@extends($theme->layoutView())

@section('content')
    <div
        class="space-y-6"
        data-public-shell-fixture="{{ $fixtureVersion }}"
        data-public-preview-state="{{ $publicShellPreviewState }}"
        data-browser-acceptance="{{ $acceptance ? 'pending' : 'not-requested' }}"
    >
        <p class="text-foundation-caption font-semibold uppercase tracking-wide text-foundation-accent-strong">
            {{ $theme->layout->value }} shell
        </p>
        <h2 class="text-foundation-display font-semibold">Public presentation foundation</h2>
        <p class="max-w-2xl text-foundation-body text-foundation-text-muted">
            This deterministic shell contains layout, locale, and SEO integration points without catalog data.
        </p>
        <div class="grid gap-4 sm:grid-cols-2" aria-label="Foundation integration points">
            <section class="rounded-foundation-card border border-foundation-border bg-foundation-surface p-foundation-card shadow-foundation-card">
                <h3 class="text-foundation-title font-semibold">Theme boundary</h3>
                <p class="mt-2 text-foundation-label text-foundation-text-muted">{{ $theme->identifier->value }}</p>
            </section>
            <section class="rounded-foundation-card border border-foundation-border bg-foundation-surface p-foundation-card shadow-foundation-card">
                <h3 class="text-foundation-title font-semibold">Runtime locale</h3>
                <p class="mt-2 text-foundation-label text-foundation-text-muted">{{ $locale }} · EUR · Europe/Berlin</p>
            </section>
        </div>
    </div>
@endsection

@if ($acceptance)
    @push('head')
        <script>
            window.__publicShellAcceptanceErrors = [];
            window.addEventListener('error', (event) => window.__publicShellAcceptanceErrors.push(event.message));
            window.addEventListener('unhandledrejection', (event) => window.__publicShellAcceptanceErrors.push(String(event.reason)));
        </script>
    @endpush

    @push('scripts')
        <script>
            const fixture = document.querySelector('[data-public-shell-fixture]');
            const failures = [];
            const verify = (condition, message) => condition || failures.push(message);
            const expectedLayout = fixture.dataset.publicPreviewState.startsWith('single-') ? 'single-category' : 'multi-category';

            verify(document.body.dataset.publicLayout === expectedLayout, 'resolved layout marker');
            verify(document.querySelector('[data-public-header]') !== null, 'semantic public header');
            verify(document.querySelector('main#main-content') !== null, 'semantic public main');
            verify(document.querySelector('[data-public-footer]') !== null, 'semantic public footer');
            verify(document.querySelector('[data-public-locale-selector]') !== null, 'enabled locale selector');
            verify(document.querySelector('[data-central-shell], [data-site-shell]') === null, 'admin shell isolation');
            verify(
                Array.from(document.querySelectorAll('link[rel="stylesheet"]')).every((link) => !/central-admin|site-admin/.test(link.href)),
                'admin asset isolation',
            );
            verify(window.__publicShellAcceptanceErrors.length === 0, 'unexpected runtime errors');

            fixture.dataset.browserAcceptance = failures.length === 0 ? 'passed' : 'failed';
            fixture.dataset.browserAcceptanceFailures = failures.join(', ');
        </script>
    @endpush
@endif
