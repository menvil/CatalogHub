<section
    {{ $attributes->class('rounded-admin-card border border-admin-border bg-admin-surface p-admin-card shadow-admin-card') }}
    aria-label="Current site context"
    data-site-context-header
>
    <div class="flex flex-col gap-admin-card lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-admin-muted">Current site</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <p class="min-w-0 truncate text-lg font-semibold text-admin-text">{{ $context->site->name }}</p>
                <x-admin.status-badge
                    :label="$context->site->status->name"
                    :variant="$statusVariant()"
                    size="sm"
                />
            </div>
            <p class="mt-2 break-all text-sm text-admin-muted">{{ $context->domain->host }}</p>
        </div>

        <dl class="grid shrink-0 grid-cols-2 gap-x-admin-card gap-y-2 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-xs uppercase tracking-wide text-admin-muted">Market</dt>
                <dd class="mt-1 font-medium text-admin-text">{{ $context->market->name }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-admin-muted">Locale</dt>
                <dd class="mt-1 font-medium text-admin-text">{{ $context->resolvedLocale }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-admin-muted">Currency</dt>
                <dd class="mt-1 font-medium text-admin-text">{{ $context->currencyCode }}</dd>
            </div>
        </dl>
    </div>
</section>
