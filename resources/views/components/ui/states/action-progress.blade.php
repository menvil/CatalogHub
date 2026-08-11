@props([
    'progress',
    'actionLabel' => 'Start action',
    'retryLabel' => 'Retry',
    'resetLabel' => 'Dismiss',
])

@php
    throw_unless($progress instanceof \App\ViewModels\ActionProgressViewModel, \InvalidArgumentException::class, 'Action progress requires an ActionProgressViewModel.');
    $tone = match ($progress->status) {
        \App\Enums\ActionProgressStatus::Idle => 'neutral',
        \App\Enums\ActionProgressStatus::Pending => 'info',
        \App\Enums\ActionProgressStatus::Success => 'success',
        \App\Enums\ActionProgressStatus::Failure => 'danger',
    };
@endphp

<section
    {{ $attributes->class('rounded-admin-card border border-admin-border bg-admin-surface p-admin-card') }}
    data-ui-action-progress="{{ $progress->status->value }}"
    data-action-progress-started="{{ $progress->status === \App\Enums\ActionProgressStatus::Pending ? 'true' : 'false' }}"
    aria-live="polite"
    @if ($progress->status === \App\Enums\ActionProgressStatus::Pending) aria-busy="true" @endif
>
    <div class="flex flex-wrap items-start justify-between gap-admin-field">
        <div class="min-w-0">
            <x-ui.status-badge :label="ucfirst($progress->status->value)" :tone="$tone" />
            <p class="mt-admin-field text-sm text-admin-text">{{ $progress->message }}</p>
        </div>
        <div class="flex flex-wrap gap-admin-field">
            @switch($progress->status)
                @case(\App\Enums\ActionProgressStatus::Idle)
                    <x-ui.button type="button" data-action-progress-start>{{ $actionLabel }}</x-ui.button>
                    @break
                @case(\App\Enums\ActionProgressStatus::Pending)
                    <x-ui.button type="button" loading disabled>{{ $actionLabel }}</x-ui.button>
                    @break
                @case(\App\Enums\ActionProgressStatus::Success)
                    <x-ui.button type="button" variant="secondary" data-action-progress-reset>{{ $resetLabel }}</x-ui.button>
                    @break
                @case(\App\Enums\ActionProgressStatus::Failure)
                    <x-ui.button type="button" variant="secondary" data-action-progress-retry>{{ $retryLabel }}</x-ui.button>
                    @break
            @endswitch
        </div>
    </div>

    @if ($progress->startedAt !== null)
        <dl class="mt-admin-card grid gap-admin-field text-sm sm:grid-cols-2">
            <div><dt class="text-admin-muted">Started</dt><dd><x-ui.timestamp :value="$progress->startedAt" timezone="UTC" /></dd></div>
            @if ($progress->completedAt !== null)
                <div><dt class="text-admin-muted">Finished</dt><dd><x-ui.timestamp :value="$progress->completedAt" timezone="UTC" /></dd></div>
            @endif
        </dl>
    @endif
</section>
