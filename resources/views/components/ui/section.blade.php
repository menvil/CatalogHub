@props(['title', 'description' => null])

<section {{ $attributes->class('space-y-admin-field') }} data-ui-section>
    <header class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-admin-field">
        <div>
            <h3 class="text-sm font-semibold text-admin-text">{{ $title }}</h3>
            @if (filled($description))<p class="mt-1 text-sm text-admin-muted">{{ $description }}</p>@endif
        </div>
        @isset($actions)<div class="flex flex-wrap gap-admin-field">{{ $actions }}</div>@endisset
    </header>
    <div>{{ $slot }}</div>
</section>
