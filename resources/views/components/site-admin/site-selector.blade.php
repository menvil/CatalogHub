<section
    {{ $attributes->class('rounded-admin-card border border-admin-border bg-admin-surface p-admin-card shadow-admin-card') }}
    aria-label="Site selector"
    data-site-selector
>
    <p class="text-xs font-semibold uppercase tracking-wide text-admin-muted">Current site</p>
    <p class="mt-1 truncate text-base font-semibold text-admin-text">{{ $currentSite->name }}</p>

    @if ($sites->count() > 1)
        <details class="mt-3">
            <summary class="cursor-pointer rounded-admin-input border border-admin-border px-3 py-2 text-sm font-medium text-admin-text focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary">
                Switch site
            </summary>
            <ul class="mt-2 space-y-1" aria-label="Authorized sites">
                @foreach ($sites as $site)
                    <li>
                        @if ($site->is($currentSite))
                            <span
                                class="block rounded-admin-input bg-admin-primary-soft px-3 py-2 text-sm font-medium text-admin-primary"
                                aria-current="true"
                                data-site-selector-current
                            >
                                {{ $site->name }}
                            </span>
                        @else
                            <a
                                href="{{ route('filament.site.pages.home', ['site_id' => $site->getKey()], absolute: false) }}"
                                class="block rounded-admin-input px-3 py-2 text-sm text-admin-muted hover:bg-admin-surface-muted hover:text-admin-text"
                                data-site-selector-link
                            >
                                {{ $site->name }}
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </details>
    @else
        <p class="mt-2 text-sm text-admin-muted">Only assigned site</p>
    @endif
</section>
