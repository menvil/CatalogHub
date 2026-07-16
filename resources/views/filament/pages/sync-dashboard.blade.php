<x-filament-panels::page>
    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <header class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Recent sync runs</h2>
            </header>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-950">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Operation</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Site</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Status</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Finished</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse ($this->recentLogs() as $log)
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-950 dark:text-white">
                                    <a class="hover:underline" href="{{ \App\Filament\Resources\SyncLogResource::getUrl('view', ['record' => $log]) }}">
                                        {{ $log->operation }}
                                    </a>
                                </td>
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $log->site?->name ?? 'All sites' }}</td>
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $log->status }}</td>
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $log->finished_at?->diffForHumans() ?? 'In progress' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">No sync runs recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <header class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Sites sync status</h2>
            </header>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-950">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Site</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Stale products</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Last sync</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse ($this->siteStatuses() as $siteStatus)
                            <tr>
                                <td class="px-5 py-3">
                                    <span class="font-medium text-gray-950 dark:text-white">{{ $siteStatus['name'] }}</span>
                                    <span class="ml-1 text-gray-500">{{ $siteStatus['code'] }}</span>
                                </td>
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $siteStatus['stale_products'] }}</td>
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $siteStatus['last_sync_at'] ?: 'Never' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">No sites configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
