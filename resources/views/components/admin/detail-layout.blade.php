<div {{ $attributes->class('space-y-admin-section') }} data-admin-detail-layout>
    <div class="grid gap-admin-section lg:grid-cols-[minmax(0,1fr)_minmax(16rem,22rem)]">
        <main class="min-w-0 space-y-admin-section" data-admin-detail-main>{{ $main ?? $slot }}</main>
        @isset($aside)<aside class="min-w-0 space-y-admin-section" data-admin-detail-aside>{{ $aside }}</aside>@endisset
    </div>
    @isset($actions)
        <div class="sticky bottom-0 z-10 rounded-admin-card border border-admin-border bg-admin-surface/95 p-admin-card shadow-admin-floating backdrop-blur" data-admin-sticky-actions>
            {{ $actions }}
        </div>
    @endisset
</div>
