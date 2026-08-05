@props(['filters' => [], 'clearAllUrl'])

@php
    $isSafeUrl = static function (mixed $value): bool {
        if (! is_string($value) || trim($value) === '') {
            return false;
        }

        $value = trim($value);
        $scheme = parse_url($value, PHP_URL_SCHEME);

        return (str_starts_with($value, '/') && ! str_starts_with($value, '//'))
            || in_array($scheme, ['http', 'https'], true);
    };

    throw_unless($isSafeUrl($clearAllUrl), \InvalidArgumentException::class, 'Active filters require a safe clear-all URL.');
@endphp

@if ($filters !== [])
    <nav {{ $attributes->class('flex flex-wrap items-center gap-2') }} aria-label="Active filters" data-admin-active-filters>
        @foreach ($filters as $filter)
            @php
                throw_unless($isSafeUrl($filter['removeUrl'] ?? null), \InvalidArgumentException::class, 'Active filters require safe removal URLs.');
            @endphp
            <a href="{{ $filter['removeUrl'] }}" class="rounded-full border border-admin-border bg-admin-surface-muted px-3 py-1 text-sm text-admin-text">
                {{ $filter['label'] ?? $filter['key'] ?? 'Filter' }} <span aria-hidden="true">×</span>
            </a>
        @endforeach
        <a href="{{ $clearAllUrl }}" class="text-sm font-semibold text-admin-primary" data-admin-clear-all-filters>Clear all</a>
    </nav>
@endif
