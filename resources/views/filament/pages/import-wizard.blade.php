<x-filament-panels::page>
    <form wire:submit="startImport" class="space-y-6">
        <x-admin.stepper-wizard
            :steps="[
                ['key' => 'source', 'label' => 'Source', 'status' => $sourceId ? 'completed' : 'current'],
                ['key' => 'artifact', 'label' => 'Artifact', 'status' => ! $sourceId ? 'pending' : ($artifact ? 'completed' : 'current')],
                ['key' => 'confirm', 'label' => 'Confirm', 'status' => $sourceId && $artifact ? 'current' : 'pending'],
            ]"
            current-step="{{ ! $sourceId ? 'source' : ($artifact ? 'confirm' : 'artifact') }}"
        />

        <x-admin.card title="Import source" description="Choose an active adapter and upload its original artifact.">
            <div class="grid gap-5 md:grid-cols-2">
                <label class="space-y-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <span>Source</span>
                    <select wire:model="sourceId" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-950">
                        <option value="">Select source</option>
                        @foreach ($this->getSources() as $source)
                            <option value="{{ $source->id }}">{{ $source->name }} ({{ $source->type }})</option>
                        @endforeach
                    </select>
                    @error('sourceId') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <span>Locale (optional)</span>
                    <input wire:model="locale" type="text" placeholder="bg-BG" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-950">
                    @error('locale') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
            </div>

            <label class="mt-5 block space-y-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                <span>Original import file</span>
                <input wire:model="artifact" type="file" class="block w-full text-sm">
                @error('artifact') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
            </label>
        </x-admin.card>

        @if ($createdBatchId)
            <div @if (in_array($batchStatus, ['pending', 'processing'], true)) wire:poll.2s="refreshBatchStatus" @endif>
                <x-admin.card
                    :variant="$batchStatus === 'failed' ? 'danger' : ($batchStatus === 'completed' ? 'success' : 'default')"
                    title="Import batch created"
                >
                    Batch #{{ $createdBatchId }} status: {{ $batchStatus }}.
                    @if ($batchStatus === 'completed')
                        The original artifact and raw products are available for review.
                    @elseif (in_array($batchStatus, ['pending', 'processing'], true))
                        This page will update automatically when background processing finishes.
                    @endif
                </x-admin.card>
            </div>
        @endif

        <div class="flex justify-end">
            <x-filament::button
                type="submit"
                icon="heroicon-o-play"
                :disabled="$createdBatchId !== null && $batchStatus !== 'failed'"
                wire:loading.attr="disabled"
                wire:target="startImport"
            >Start import</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
