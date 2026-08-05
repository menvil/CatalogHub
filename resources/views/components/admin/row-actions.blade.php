@props(['rowId', 'actions' => []])

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
@endphp

<nav {{ $attributes->class('flex flex-wrap items-center justify-end gap-2') }} aria-label="Actions for row {{ $rowId }}" data-admin-row-actions="{{ $rowId }}">
    @foreach ($actions as $action)
        @php(throw_unless($isSafeUrl($action['url'] ?? null), \InvalidArgumentException::class, 'Row actions require safe URLs.'))
        <a
            href="{{ $action['url'] }}"
            class="text-sm font-semibold {{ ($action['destructive'] ?? false) ? 'text-admin-danger' : 'text-admin-primary' }}"
            @if ($action['destructive'] ?? false) data-destructive-action @endif
        >{{ $action['label'] }}</a>
    @endforeach
</nav>
