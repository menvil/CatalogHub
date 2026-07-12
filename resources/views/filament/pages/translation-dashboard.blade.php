<x-filament-panels::page>
    @include('central-admin.translations.partials.dashboard-content', ['stats' => $this->getStats()])
</x-filament-panels::page>
