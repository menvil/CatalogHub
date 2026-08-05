@props(['value', 'timezone', 'relativeHint' => null, 'empty' => 'Not available'])

@php
    try {
        $targetTimezone = new \DateTimeZone($timezone);
    } catch (\Exception) {
        throw new \InvalidArgumentException("Invalid timestamp timezone [{$timezone}].");
    }

    if ($value !== null && ! $value instanceof \DateTimeInterface) {
        throw new \InvalidArgumentException('Timestamp values must implement DateTimeInterface or be null.');
    }

    $absolute = $value === null
        ? null
        : \DateTimeImmutable::createFromInterface($value)->setTimezone($targetTimezone);
@endphp

<span {{ $attributes->class('inline-flex flex-col text-sm') }} data-ui-timestamp>
    @if ($absolute === null)
        <span class="text-admin-muted">{{ $empty }}</span>
    @else
        <time datetime="{{ $value->format(DATE_ATOM) }}" class="text-admin-text">{{ $absolute->format('Y-m-d H:i T') }}</time>
        @if ($relativeHint)
            <span class="text-xs text-admin-muted">{{ $relativeHint }}</span>
        @endif
    @endif
</span>
