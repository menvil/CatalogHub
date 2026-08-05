@props(['rowId', 'actions' => []])

<nav {{ $attributes->class('flex flex-wrap items-center justify-end gap-2') }} aria-label="Actions for row {{ $rowId }}" data-admin-row-actions="{{ $rowId }}">
    @foreach ($actions as $action)
        @php
            $destructive = (bool) ($action['destructive'] ?? false);
            throw_unless(\App\Support\Presentation\SafePresentationUrl::allows($action['url'] ?? null), \InvalidArgumentException::class, 'Row actions require safe URLs.');
            throw_if($destructive && trim((string) ($action['confirmationId'] ?? '')) === '', \InvalidArgumentException::class, 'Destructive row actions require a confirmation ID.');
        @endphp
        @if ($destructive)
            <button type="button" class="text-sm font-semibold text-admin-danger" aria-haspopup="dialog" aria-controls="{{ $action['confirmationId'] }}" data-destructive-action data-admin-modal-open-target="{{ $action['confirmationId'] }}">{{ $action['label'] }}</button>
        @else
            <a href="{{ $action['url'] }}" class="text-sm font-semibold text-admin-primary">{{ $action['label'] }}</a>
        @endif
    @endforeach
</nav>
