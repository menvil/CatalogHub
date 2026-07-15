<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Price Update Queue Monitor</x-slot>
        <x-slot name="description">Site-scoped status for enabled price sources.</x-slot>

        @php
            $status = $this->getStatus();
        @endphp

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Pending jobs</div>
                <div class="text-2xl font-semibold">{{ $status->pendingJobsCount }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Running jobs</div>
                <div class="text-2xl font-semibold">{{ $status->runningJobsCount }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Failed jobs</div>
                <div class="text-2xl font-semibold">{{ $status->failedJobsCount }}</div>
            </div>
        </div>

        <div class="mt-4 space-y-2 border-t border-gray-200 pt-4 text-sm dark:border-white/10">
            <p>
                <span class="font-medium">Last sync activity:</span>
                {{ $status->lastSyncAt?->diffForHumans() ?? 'No sync activity yet' }}
            </p>

            @if($status->recentFailedSource !== null)
                <div class="rounded-lg bg-danger-50 p-3 text-danger-700 dark:bg-danger-400/10 dark:text-danger-300">
                    <div class="font-medium">Recent failure: {{ $status->recentFailedSource }}</div>
                    @if($status->recentFailureMessage !== null)
                        <div>{{ $status->recentFailureMessage }}</div>
                    @endif
                </div>
            @endif

            <a class="font-medium text-primary-600 hover:underline dark:text-primary-400" href="{{ $this->getSyncStatusUrl() }}">
                View sync status
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
