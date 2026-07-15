<x-filament-panels::page>
    @php
        $preview = $this->getPreview();
        $modeLabels = [
            'normalized' => 'Normalized offers',
            'auto' => 'Normalized offers with widget fallback',
            'widget' => 'External widget',
        ];
    @endphp

    <div class="grid gap-4 lg:grid-cols-3">
        <x-admin.card title="Provider mode">
            <p class="text-lg font-semibold">{{ $modeLabels[$preview->providerMode] }}</p>
        </x-admin.card>

        <x-admin.card title="Last successful source sync">
            <p class="text-lg font-semibold">{{ $preview->lastSuccessfulSyncAt?->diffForHumans() ?? 'No successful sync yet' }}</p>
        </x-admin.card>

        <x-admin.card title="Widget fallback">
            @if ($preview->widgetEnabled)
                <p class="text-lg font-semibold">Widget fallback enabled</p>
                <p class="mt-1 text-sm text-gray-500">Provider: {{ $preview->widgetProvider ?? 'Not selected' }}</p>
            @else
                <p class="text-lg font-semibold">Widget fallback disabled</p>
            @endif
        </x-admin.card>
    </div>

    <x-admin.card title="Enabled price sources">
        @if ($preview->enabledSources === [])
            <p class="text-sm text-gray-500">No active sources are available for this market.</p>
        @else
            <ul class="divide-y divide-gray-200">
                @foreach ($preview->enabledSources as $source)
                    <li class="flex items-center justify-between gap-4 py-3">
                        <span class="font-medium">{{ $source['name'] }}</span>
                        <span class="text-sm text-gray-500">{{ str($source['status'])->headline() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>

    <x-admin.card title="Sample product offer preview">
        @if ($preview->sampleOffers === [])
            <p class="text-sm text-gray-500">No visible product with usable offers is available for preview.</p>
        @else
            <h2 class="font-semibold">{{ $preview->sampleProductName }}</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead><tr><th class="py-2">Merchant</th><th class="py-2">Price</th><th class="py-2">Availability</th></tr></thead>
                    <tbody>
                        @foreach ($preview->sampleOffers as $offer)
                            <tr class="border-t border-gray-200">
                                <td class="py-2 font-medium">{{ $offer['merchant'] }}</td>
                                <td class="py-2">{{ $offer['price'] }}</td>
                                <td class="py-2">{{ $offer['availability'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.card>
</x-filament-panels::page>
