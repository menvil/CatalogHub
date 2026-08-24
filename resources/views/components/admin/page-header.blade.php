<header
    {{ $attributes->class('flex flex-col gap-admin-field border-b border-admin-border pb-admin-section md:flex-row md:items-start md:justify-between') }}
    data-screen-id="{{ $screenId }}"
>
    <div class="min-w-0 space-y-2">
        <x-admin.breadcrumbs :items="$breadcrumbs" />

        <div class="flex flex-wrap items-center gap-2">
            @if ($showScreenId)
                <span class="font-foundation-mono text-xs font-semibold text-admin-muted">{{ $screenId }}</span>
            @endif
            @if ($status)
                <span class="rounded-admin-badge bg-admin-info-soft px-2 py-1 text-xs font-medium text-admin-info">{{ $status }}</span>
            @endif
        </div>

        <h1 class="break-words text-foundation-heading font-semibold text-admin-text">{{ $title }}</h1>

        @if ($description)
            <p class="max-w-3xl text-sm text-admin-muted">{{ $description }}</p>
        @endif
    </div>

    @if (isset($actions) && $actions->isNotEmpty())
        <div class="flex w-full min-w-0 flex-wrap items-center gap-admin-field md:w-auto md:shrink-0" data-page-actions>
            {{ $actions }}
        </div>
    @endif
</header>
