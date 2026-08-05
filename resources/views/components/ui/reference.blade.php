@props(['label', 'kind', 'url' => null, 'empty' => 'Not available'])

@php
    $label = trim((string) $label);
    $rawUrl = is_string($url) ? trim($url) : null;
    $safeUrl = $rawUrl !== null && \App\Support\Presentation\SafePresentationUrl::allows($rawUrl);
    throw_if($rawUrl !== null && ! $safeUrl, \InvalidArgumentException::class, 'References require a safe URL.');
@endphp

<span {{ $attributes->class('inline-flex min-w-0 flex-col') }} data-ui-reference>
    <span class="text-xs text-admin-muted">{{ $kind }}</span>
    @if ($label === '')
        <span class="text-sm text-admin-muted">{{ $empty }}</span>
    @elseif ($rawUrl !== null)
        <a href="{{ $rawUrl }}" class="truncate text-sm font-medium text-admin-primary hover:underline">{{ $label }}</a>
    @else
        <span class="truncate text-sm font-medium text-admin-text">{{ $label }}</span>
    @endif
</span>
