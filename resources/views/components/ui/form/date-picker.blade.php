@props([
    'id', 'name', 'label', 'value' => null, 'help' => null, 'error' => null,
    'required' => false, 'disabled' => false, 'min' => null, 'max' => null,
    'withTime' => false,
])
@php
    $rawValue = trim((string) ($value ?? ''));
    $dateValue = preg_match('/^\d{4}-\d{2}-\d{2}/', $rawValue) === 1 ? substr($rawValue, 0, 10) : '';
    $timeValue = $withTime && preg_match('/T(\d{2}:\d{2})/', $rawValue, $match) === 1 ? $match[1] : '00:00';
    $parsedDate = $dateValue !== '' ? \DateTimeImmutable::createFromFormat('!Y-m-d', $dateValue) : false;
    if ($parsedDate instanceof \DateTimeImmutable && $parsedDate->format('Y-m-d') !== $dateValue) {
        $parsedDate = false;
    }
    $displayValue = $parsedDate instanceof \DateTimeImmutable
        ? $parsedDate->format('d M Y').($withTime ? ', '.$timeValue : '')
        : 'Choose '.strtolower($label);
    $describedBy = collect([
        $attributes->get('aria-describedby'),
        filled($help) ? "{$id}-help" : null,
        filled($error) ? "{$id}-error" : null,
    ])->filter(fn (mixed $item): bool => is_string($item) && trim($item) !== '')->implode(' ');
@endphp
<x-ui.form.field :id="$id" :control-id="$id.'-trigger'" :label="$label" :required="$required" :help="$help" :error="$error">
    <div
        class="relative"
        data-ui-date-picker="{{ $id }}"
        data-ui-date-picker-mode="{{ $withTime ? 'datetime' : 'date' }}"
        data-min="{{ $min }}"
        data-max="{{ $max }}"
    >
        <input id="{{ $id }}" name="{{ $name }}" type="hidden" value="{{ $rawValue }}" data-ui-date-picker-value @disabled($disabled)>
        <button
            id="{{ $id }}-trigger"
            type="button"
            class="flex min-h-10 w-full cursor-pointer items-center justify-between gap-3 rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-left text-sm text-admin-text hover:bg-admin-surface-muted focus:border-admin-primary focus:outline-none focus:ring-2 focus:ring-admin-primary/20 disabled:cursor-not-allowed disabled:opacity-60"
            aria-haspopup="dialog"
            aria-expanded="false"
            aria-controls="{{ $id }}-panel"
            @if (filled($error)) aria-invalid="true" @endif
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            data-ui-date-picker-trigger
            @disabled($disabled)
        >
            <span class="truncate" data-ui-date-picker-display>{{ $displayValue }}</span>
            <x-ui.icon name="calendar-days" decorative size="sm" class="cursor-pointer text-admin-muted" />
        </button>

        <div id="{{ $id }}-panel" class="absolute left-0 z-40 mt-1 w-80 max-w-[calc(100vw-3rem)] rounded-admin-card border border-admin-border bg-admin-surface p-3 shadow-admin-modal" role="dialog" aria-label="Choose {{ $label }}" data-ui-date-picker-panel hidden>
            <div class="flex items-center justify-between gap-2">
                <button type="button" class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-admin-input text-admin-muted hover:bg-admin-surface-muted hover:text-admin-text focus:outline-none focus:ring-2 focus:ring-admin-primary/20" aria-label="Previous month" data-ui-date-picker-previous>
                    <x-ui.icon name="chevron-down" decorative size="sm" class="rotate-90" />
                </button>
                <p class="text-sm font-semibold text-admin-text" aria-live="polite" data-ui-date-picker-month></p>
                <button type="button" class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-admin-input text-admin-muted hover:bg-admin-surface-muted hover:text-admin-text focus:outline-none focus:ring-2 focus:ring-admin-primary/20" aria-label="Next month" data-ui-date-picker-next>
                    <x-ui.icon name="chevron-down" decorative size="sm" class="-rotate-90" />
                </button>
            </div>
            <div class="mt-2 grid grid-cols-7 text-center text-xs font-medium text-admin-muted" aria-hidden="true">
                @foreach (['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'] as $day)<span class="py-1">{{ $day }}</span>@endforeach
            </div>
            <div class="grid grid-cols-7 gap-1" data-ui-date-picker-grid></div>
            @if ($withTime)
                <div class="mt-3 flex items-center justify-between gap-3 border-t border-admin-border pt-3">
                    <label for="{{ $id }}-time" class="text-sm font-medium text-admin-text">Time</label>
                    <input id="{{ $id }}-time" type="time" value="{{ $timeValue }}" class="min-h-10 cursor-pointer rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm text-admin-text focus:border-admin-primary focus:outline-none focus:ring-2 focus:ring-admin-primary/20" data-ui-date-picker-time>
                </div>
            @endif
            <div class="mt-3 flex justify-end border-t border-admin-border pt-3">
                <button type="button" class="cursor-pointer rounded-admin-input bg-admin-primary px-3 py-2 text-sm font-semibold text-white hover:bg-admin-primary-hover focus:outline-none focus:ring-2 focus:ring-admin-primary/30" data-ui-date-picker-done>Done</button>
            </div>
        </div>
    </div>
</x-ui.form.field>
