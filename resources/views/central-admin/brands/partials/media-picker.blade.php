<section id="shared-media-picker" class="scroll-mt-6" data-screen-region="shared-media-workspace">
    <x-admin.card
        title="Choose from Shared Media"
        description="Search and select an existing compatible asset from Shared Media."
    >
        @if ($availableAssets !== null)
            <div id="shared-media-results" data-screen-region="shared-media-picker">
            <form method="GET" action="{{ route('central.brands.media', $brand, absolute: false).'#shared-media-picker' }}" class="grid min-w-0 gap-admin-field sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                <input type="hidden" name="picker" value="1">
                <label class="min-w-0 text-sm font-medium text-admin-text">
                    Search shared media
                    <input
                        type="search"
                        name="asset_search"
                        value="{{ $assetSearch }}"
                        maxlength="255"
                        class="mt-1 w-full min-w-0 rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2"
                        placeholder="Filename, checksum or asset ID"
                    >
                </label>
                <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
            </form>

            @error('media_asset_id')
                <p class="mt-3 rounded-admin-input border border-admin-danger/30 bg-admin-danger-soft px-3 py-2 text-sm font-medium text-admin-danger" role="alert">{{ $message }}</p>
            @enderror

            @if ($availableAssets->isEmpty())
                <div class="mt-4 rounded-admin-card border border-dashed border-admin-border bg-admin-surface-muted px-4 py-8 text-center">
                    <p class="text-sm font-semibold text-admin-text">No compatible shared assets</p>
                    <p class="mt-1 text-sm text-admin-muted">Try another filename, checksum or asset ID.</p>
                </div>
            @else
                <div class="mt-4 grid min-w-0 grid-cols-1 gap-3 min-[30rem]:grid-cols-2 md:grid-cols-4 xl:grid-cols-8" data-shared-media-results>
                    @foreach ($availableAssets as $candidate)
                        @php
                            $candidateLogo = $availableLogos->get((int) $candidate->getKey());
                            $isCurrent = $asset?->is($candidate) === true;
                        @endphp
                        <article @class([
                            'min-w-0 overflow-hidden rounded-admin-card border bg-admin-surface',
                            'border-admin-primary ring-1 ring-admin-primary/20' => $isCurrent,
                            'border-admin-border' => ! $isCurrent,
                        ]) data-media-asset-card="{{ $candidate->id }}" @if ($isCurrent) aria-current="true" @endif>
                            <div class="relative flex aspect-[16/9] items-center justify-center border-b border-admin-border bg-admin-surface-muted p-3">
                                @if ($candidateLogo?->url)
                                    <img src="{{ $candidateLogo->url }}" alt="" class="h-full w-full object-contain" loading="lazy">
                                @else
                                    <span class="text-xs font-medium text-admin-muted">Unavailable preview</span>
                                @endif
                                @if ($isCurrent)
                                    <span class="absolute left-2 top-2 rounded-admin-badge bg-admin-primary px-2 py-1 text-[0.6875rem] font-semibold text-white">Current</span>
                                @endif
                            </div>
                            <div class="min-w-0 p-3">
                                <h4 class="truncate text-sm font-semibold text-admin-text" title="{{ $candidate->original_filename ?: $candidate->uuid }}">{{ $candidate->original_filename ?: $candidate->uuid }}</h4>
                                <p class="mt-1 truncate text-xs text-admin-muted">{{ $candidate->mime_type }} · {{ $candidate->width && $candidate->height ? $candidate->width.' × '.$candidate->height.' px' : 'Dimensions unavailable' }}</p>
                                <form method="POST" action="{{ route('central.brands.media.logo.assign', $brand) }}" class="mt-3">
                                    @csrf
                                    <input type="hidden" name="media_asset_id" value="{{ $candidate->id }}">
                                    <x-ui.button
                                        type="submit"
                                        variant="secondary"
                                        class="w-full"
                                        :disabled="$isCurrent || $candidateLogo?->url === null"
                                    >{{ $isCurrent ? 'Current logo' : 'Use as logo' }}</x-ui.button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="mt-4 min-w-0 overflow-x-auto">{{ $availableAssets->links() }}</div>
            @endif
            </div>
        @endif
    </x-admin.card>
</section>
