<x-filament-panels::page>
    @php($themes = $this->getThemeOptions())

    @if ($themes === [])
        <x-admin.empty-state
            title="No active themes"
            description="Register and validate a theme manifest before selecting a theme for this site."
        />
    @else
        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($themes as $option)
                @php($theme = $option['theme'])

                <x-admin.card
                    :title="$theme->name"
                    :description="$theme->description"
                    :variant="$option['current'] ? 'success' : 'default'"
                >
                    <x-slot:actions>
                        @if ($option['current'])
                            <x-admin.status-badge label="Current" variant="success" />
                        @elseif ($option['compatible'])
                            <x-admin.status-badge label="Compatible" variant="success" />
                        @else
                            <x-admin.status-badge label="Incompatible" variant="danger" />
                        @endif
                    </x-slot:actions>

                    <div class="space-y-4">
                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-admin-muted">Code</dt>
                                <dd class="font-medium text-admin-text">{{ $theme->code }}</dd>
                            </div>
                            <div>
                                <dt class="text-admin-muted">Version</dt>
                                <dd class="font-medium text-admin-text">{{ $theme->version ?: 'Not specified' }}</dd>
                            </div>
                        </dl>

                        <div>
                            <h3 class="text-sm font-medium text-admin-text">Supported capabilities</h3>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @forelse ($option['supports'] as $capability)
                                    <x-admin.status-badge :label="$capability" variant="neutral" size="sm" />
                                @empty
                                    <span class="text-sm text-admin-muted">No capabilities declared.</span>
                                @endforelse
                            </div>
                        </div>

                        @if (! $option['compatible'])
                            <div class="rounded-lg border border-admin-danger/30 bg-admin-danger-soft p-3 text-sm text-admin-danger">
                                @if ($option['missingFeatures'] !== [])
                                    Missing site features: {{ implode(', ', $option['missingFeatures']) }}.
                                @endif

                                @foreach ($option['warnings'] as $warning)
                                    <p>{{ $warning }}</p>
                                @endforeach
                            </div>
                        @endif

                        <button
                            type="button"
                            @if ($option['compatible'] && ! $option['current'])
                                wire:click="activate({{ $theme->getKey() }})"
                            @else
                                disabled
                            @endif
                            class="fi-btn fi-btn-size-md inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-semibold {{ $option['compatible'] && ! $option['current'] ? 'bg-primary-600 text-white' : 'cursor-not-allowed bg-gray-200 text-gray-500' }}"
                        >
                            {{ $option['current'] ? 'Active theme' : 'Activate' }}
                        </button>
                    </div>
                </x-admin.card>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
