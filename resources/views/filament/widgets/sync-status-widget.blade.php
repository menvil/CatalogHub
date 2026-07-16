@php
    $summary = $this->summary();
@endphp

<x-filament-widgets::widget>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" data-sync-status-summary>
        @foreach ([
            ['label' => 'Stale products', 'value' => $summary['stale_products'], 'tone' => 'warning'],
            ['label' => 'Failed projections', 'value' => $summary['failed_projections'], 'tone' => 'danger'],
            ['label' => 'Open conflicts', 'value' => $summary['open_conflicts'], 'tone' => 'warning'],
            ['label' => 'Sites', 'value' => $summary['sites'], 'tone' => 'primary'],
        ] as $metric)
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $metric['value'] }}</p>
            </section>
        @endforeach
    </div>
</x-filament-widgets::widget>
