@props(['rowId', 'actions' => [], 'display' => 'inline'])

@php
    throw_unless(in_array($display, ['inline', 'menu'], true), \InvalidArgumentException::class, "Unknown row action display [{$display}].");
@endphp

<nav {{ $attributes->class('flex flex-wrap items-center justify-end gap-2') }} aria-label="Actions for row {{ $rowId }}" data-admin-row-actions="{{ $rowId }}">
    @if ($display === 'menu')
        <details class="admin-row-actions-menu" data-admin-row-actions-menu>
            <summary aria-label="Open actions for row {{ $rowId }}" aria-haspopup="menu"><span aria-hidden="true">⋮</span></summary>
            <div role="menu">
    @endif
    @foreach ($actions as $action)
        @php
            $destructive = (bool) ($action['destructive'] ?? false);
            throw_unless(\App\Support\Presentation\SafePresentationUrl::allows($action['url'] ?? null), \InvalidArgumentException::class, 'Row actions require safe URLs.');
            throw_if($destructive && trim((string) ($action['confirmationId'] ?? '')) === '', \InvalidArgumentException::class, 'Destructive row actions require a confirmation ID.');
        @endphp
        @if ($destructive)
            <button type="button" @if ($display === 'menu') role="menuitem" @endif class="text-sm font-semibold text-admin-danger" aria-haspopup="dialog" aria-controls="{{ $action['confirmationId'] }}" data-destructive-action data-admin-modal-open-target="{{ $action['confirmationId'] }}">{{ $action['label'] }}</button>
        @else
            <a href="{{ $action['url'] }}" @if ($display === 'menu') role="menuitem" @endif class="text-sm font-semibold text-admin-primary">{{ $action['label'] }}</a>
        @endif
    @endforeach
    @if ($display === 'menu')
            </div>
        </details>
    @endif
</nav>
