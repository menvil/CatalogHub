@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
    'label' => null,
    'icon' => null,
    'loading' => false,
    'disabled' => false,
])

@php
    $variants = [
        'primary' => 'border-admin-primary bg-admin-primary text-white hover:brightness-95',
        'secondary' => 'border-admin-border bg-admin-surface text-admin-text hover:bg-admin-surface-muted',
        'tertiary' => 'border-transparent bg-transparent text-admin-muted hover:bg-admin-surface-muted hover:text-admin-text',
        'danger' => 'border-admin-danger bg-admin-danger text-white hover:brightness-95',
    ];
    $buttonTypes = ['button', 'submit', 'reset'];

    throw_unless(array_key_exists($variant, $variants), \InvalidArgumentException::class, "Unknown button variant [{$variant}].");
    throw_unless(in_array($type, $buttonTypes, true), \InvalidArgumentException::class, "Unsupported button type [{$type}].");

    $isDisabled = (bool) $disabled || (bool) $loading;
    $rawHref = is_string($href) ? trim($href) : null;
    $safeHref = $rawHref !== null && \App\Support\Presentation\SafePresentationUrl::allows($rawHref, allowFragment: true) ? $rawHref : null;
    $classes = 'inline-flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-admin-input border px-3 py-2 text-sm font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary disabled:cursor-not-allowed disabled:opacity-60 '.$variants[$variant];
@endphp

@if ($rawHref !== null && ! $isDisabled)
    @php(throw_unless($safeHref !== null, \InvalidArgumentException::class, 'Button links must use a relative, http, or https URL.'))
    <a
        href="{{ $safeHref }}"
        {{ $attributes->class($classes) }}
        data-ui-button="{{ $variant }}"
    >
        @if ($icon)<x-ui.icon :name="$icon" decorative class="h-4 w-4" />@endif
        <span>{{ $label ?? $slot }}</span>
    </a>
@elseif ($rawHref !== null)
    <span
        {{ $attributes->class([$classes, 'cursor-not-allowed opacity-60']) }}
        aria-disabled="true"
        @if ($loading) aria-busy="true" @endif
        data-ui-button="{{ $variant }}"
        data-ui-disabled-link
    >
        @if ($loading)
            <span class="h-4 w-4 animate-spin rounded-full border-2 border-current border-r-transparent" aria-hidden="true"></span>
        @elseif ($icon)
            <x-ui.icon :name="$icon" decorative class="h-4 w-4" />
        @endif
        <span>{{ $label ?? $slot }}</span>
    </span>
@else
    <button
        type="{{ $type }}"
        @disabled($isDisabled)
        @if ($loading) aria-busy="true" @endif
        {{ $attributes->class($classes) }}
        data-ui-button="{{ $variant }}"
    >
        @if ($loading)
            <span class="h-4 w-4 animate-spin rounded-full border-2 border-current border-r-transparent" aria-hidden="true"></span>
        @elseif ($icon)
            <x-ui.icon :name="$icon" decorative class="h-4 w-4" />
        @endif
        <span>{{ $label ?? $slot }}</span>
    </button>
@endif
