<div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-4">
        <x-admin.card>
            <div class="text-sm text-admin-muted">Locales</div>
            <div class="text-3xl font-semibold">{{ $stats['locales_count'] }}</div>
        </x-admin.card>
        <x-admin.card>
            <div class="text-sm text-admin-muted">Missing translations</div>
            <div class="text-3xl font-semibold">{{ $stats['missing_count'] }}</div>
        </x-admin.card>
        <x-admin.card>
            <div class="text-sm text-admin-muted">Outdated translations</div>
            <div class="text-3xl font-semibold">{{ $stats['outdated_count'] }}</div>
        </x-admin.card>
        <x-admin.card>
            <div class="text-sm text-admin-muted">Approved translations</div>
            <div class="text-3xl font-semibold">{{ $stats['approved_count'] }}</div>
        </x-admin.card>
    </div>

    <x-admin.card>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold">Translation Coverage</h2>
            <div class="flex gap-2 text-sm">
                <a class="text-admin-link" href="{{ route('central.translations.missing') }}">Missing</a>
                <a class="text-admin-link" href="{{ route('central.translations.outdated') }}">Outdated</a>
            </div>
        </div>

        <div class="space-y-4">
            @forelse ($stats['coverage_by_locale'] as $localeStats)
                <div>
                    <div class="mb-1 flex items-center justify-between text-sm">
                        <span class="font-medium">{{ $localeStats['locale'] }}</span>
                        <span>{{ $localeStats['coverage'] }}%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded bg-gray-200">
                        <div class="h-2 bg-amber-500" style="width: {{ min(100, max(0, $localeStats['coverage'])) }}%"></div>
                    </div>
                    <div class="mt-1 text-xs text-admin-muted">
                        Approved: {{ $localeStats['approved'] }} · Missing: {{ $localeStats['missing'] }} · Outdated: {{ $localeStats['outdated'] }}
                    </div>
                </div>
            @empty
                <p class="text-sm text-admin-muted">No active locales yet.</p>
            @endforelse
        </div>
    </x-admin.card>
</div>
