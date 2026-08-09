@props(['label' => 'Loading', 'rows' => 3])

@php
    $rowCount = filter_var($rows, FILTER_VALIDATE_INT);
    throw_unless(is_int($rowCount) && $rowCount >= 1 && $rowCount <= 12, \InvalidArgumentException::class, 'Loading rows must be between 1 and 12.');
@endphp

<section {{ $attributes->class('space-y-admin-field rounded-admin-card border border-admin-border bg-admin-surface p-admin-card') }} data-ui-screen-state="loading" role="status" aria-busy="true" aria-label="{{ $label }}">
    <span class="sr-only">{{ $label }}</span>
    @for ($row = 0; $row < $rowCount; $row++)
        <div class="animate-pulse motion-reduce:animate-none" data-ui-loading-row aria-hidden="true">
            <div class="h-4 rounded-admin-input bg-admin-surface-muted {{ $row % 3 === 0 ? 'w-2/3' : ($row % 3 === 1 ? 'w-full' : 'w-5/6') }}"></div>
        </div>
    @endfor
</section>
