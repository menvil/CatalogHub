<x-filament-panels::page>
    @php($availableBlocks = $this->getAvailableBlocks())

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-4 xl:col-span-2">
            <x-admin.card title="Homepage composition" description="Blocks are rendered in this order. Use the controls to reorder or disable them.">
                <div class="space-y-3">
                    @forelse ($this->getHomeBlocks() as $homeBlock)
                        <div wire:key="home-block-{{ $homeBlock->id }}" class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-admin-border p-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-admin-text">{{ $homeBlock->definition->name }}</span>
                                    <x-admin.status-badge :label="$homeBlock->enabled ? 'Enabled' : 'Disabled'" :variant="$homeBlock->enabled ? 'success' : 'neutral'" size="sm" />
                                </div>
                                <p class="mt-1 text-sm text-admin-muted">Position {{ $homeBlock->position }} · {{ $homeBlock->block_code }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-filament::button size="sm" color="gray" wire:click="move({{ $homeBlock->id }}, 'up')">Up</x-filament::button>
                                <x-filament::button size="sm" color="gray" wire:click="move({{ $homeBlock->id }}, 'down')">Down</x-filament::button>
                                <x-filament::button size="sm" color="gray" wire:click="edit({{ $homeBlock->id }})">Configure</x-filament::button>
                                <x-filament::button size="sm" :color="$homeBlock->enabled ? 'danger' : 'success'" wire:click="toggle({{ $homeBlock->id }})">
                                    {{ $homeBlock->enabled ? 'Disable' : 'Enable' }}
                                </x-filament::button>
                            </div>
                        </div>
                    @empty
                        <x-admin.empty-state title="No homepage blocks" description="Add a compatible block from the palette." />
                    @endforelse
                </div>
            </x-admin.card>

            @if ($editingBlockId !== null)
                <x-admin.card title="Block configuration" description="Configuration is validated against the registered block schema.">
                    <label for="home-block-edit-config" class="block text-sm font-medium text-admin-text">Configuration JSON</label>
                    <textarea id="home-block-edit-config" wire:model="editConfigJson" rows="10" class="mt-2 w-full rounded-lg border-admin-border font-mono text-sm"></textarea>
                    @error('editConfigJson') <p class="mt-1 text-sm text-admin-danger">{{ $message }}</p> @enderror
                    <x-filament::button class="mt-3" wire:click="saveConfig">Save configuration</x-filament::button>
                </x-admin.card>
            @endif
        </div>

        <x-admin.card title="Available blocks" description="Only blocks registered for the home page are shown.">
            <div class="space-y-4">
                @foreach ($availableBlocks as $option)
                    <div class="rounded-lg border border-admin-border p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-medium text-admin-text">{{ $option['block']->name }}</p>
                                <p class="text-xs text-admin-muted">{{ $option['block']->code }}</p>
                            </div>
                            <x-admin.status-badge :label="$option['compatible'] ? 'Compatible' : 'Unavailable'" :variant="$option['compatible'] ? 'success' : 'warning'" size="sm" />
                        </div>
                        @if (! $option['compatible'])
                            <p class="mt-2 text-sm text-admin-warning">{{ $option['reason'] }}</p>
                        @endif
                    </div>
                @endforeach

                <label for="home-block-code" class="block text-sm font-medium text-admin-text">Block</label>
                <select id="home-block-code" wire:model="selectedBlockCode" class="w-full rounded-lg border-admin-border">
                    <option value="">Select a block</option>
                    @foreach ($availableBlocks as $option)
                        <option value="{{ $option['block']->code }}" @disabled(! $option['compatible'])>{{ $option['block']->name }}</option>
                    @endforeach
                </select>
                @error('selectedBlockCode') <p class="text-sm text-admin-danger">{{ $message }}</p> @enderror

                <label for="home-block-config" class="block text-sm font-medium text-admin-text">Configuration JSON</label>
                <textarea id="home-block-config" wire:model="addConfigJson" rows="8" class="w-full rounded-lg border-admin-border font-mono text-sm"></textarea>
                @error('addConfigJson') <p class="text-sm text-admin-danger">{{ $message }}</p> @enderror

                <x-filament::button wire:click="add">Add block</x-filament::button>
            </div>
        </x-admin.card>
    </div>
</x-filament-panels::page>
