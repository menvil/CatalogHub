<x-admin.card
    id="external-identities"
    title="External identities"
    description="External identities connect this canonical Brand to records in configured import sources."
    data-screen-region="external-identities"
>
    <x-slot:actions>
        @can('catalog.brands.manage')
            @if ($activeImportSources->isNotEmpty())
                <x-ui.button
                    variant="secondary"
                    aria-haspopup="dialog"
                    aria-controls="add-brand-external-identity-modal"
                    data-admin-modal-open-target="add-brand-external-identity-modal"
                >Add identity</x-ui.button>
            @endif
        @endcan
    </x-slot:actions>

    @if ($brand->externalIdentities->isEmpty())
        <div class="rounded-admin-input border border-dashed border-admin-border bg-admin-surface-muted p-admin-card">
            <p class="text-sm font-medium text-admin-text">No external identities are linked to this Brand.</p>
            @if ($activeImportSources->isEmpty())
                <p class="mt-1 text-sm text-admin-muted">No active import sources are available.</p>
            @endif
        </div>
    @else
        <ul class="divide-y divide-admin-border" data-brand-external-identities>
            @foreach ($brand->externalIdentities as $identity)
                @php
                    $source = $identity->source;
                    $externalUrlIsSafe = \App\Support\Presentation\SafeExternalRecordUrl::allows($identity->external_url);
                    $sourceIsActive = $source->status === 'active';
                @endphp
                <li class="grid min-w-0 gap-3 py-4 first:pt-0 last:pb-0 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center" data-external-identity-id="{{ $identity->getKey() }}">
                    <div class="min-w-0 space-y-2">
                        <div class="flex min-w-0 flex-wrap items-center gap-2">
                            <span class="break-words text-sm font-semibold text-admin-text">{{ $source->name }}</span>
                            <x-admin.status-badge :label="ucfirst((string) $source->status)" :variant="$sourceIsActive ? 'success' : 'warning'" size="sm" />
                        </div>
                        <p class="break-all font-foundation-mono text-xs text-admin-muted">{{ $source->code }}</p>
                        <p class="break-all font-foundation-mono text-sm text-admin-text">{{ $identity->external_id }}</p>
                        @if ($identity->external_url === null)
                            <p class="text-xs text-admin-muted">No external URL</p>
                        @elseif ($externalUrlIsSafe)
                            <a href="{{ $identity->external_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex text-sm font-medium text-admin-primary underline decoration-admin-primary/30 underline-offset-2">Open record</a>
                        @else
                            <p class="text-xs text-admin-warning">External record URL is unavailable.</p>
                        @endif
                    </div>

                    @can('catalog.brands.manage')
                        <div class="flex flex-wrap gap-2 sm:justify-end">
                            <x-ui.button
                                variant="secondary"
                                aria-haspopup="dialog"
                                aria-controls="edit-brand-external-identity-{{ $identity->getKey() }}-modal"
                                data-admin-modal-open-target="edit-brand-external-identity-{{ $identity->getKey() }}-modal"
                            >Edit</x-ui.button>
                            <x-ui.button
                                variant="danger"
                                aria-haspopup="dialog"
                                aria-controls="remove-brand-external-identity-{{ $identity->getKey() }}-modal"
                                data-admin-modal-open-target="remove-brand-external-identity-{{ $identity->getKey() }}-modal"
                            >Remove</x-ui.button>
                        </div>
                    @endcan
                </li>
            @endforeach
        </ul>

        @if ($activeImportSources->isEmpty())
            <p class="mt-admin-card border-t border-admin-border pt-admin-card text-sm text-admin-muted">No active import sources are available for new identities.</p>
        @endif
    @endif
</x-admin.card>
