@props([
    'selectedItems' => [],
    'mode' => 'single',
    'acceptedTypes' => [],
    'emptyTitle' => 'No media selected',
    'emptyDescription' => 'Choose or upload media in a later phase.',
])

@php
    $hasItems = count($selectedItems) > 0;
@endphp

<section
    {{ $attributes->class('rounded-admin-card border border-admin-border bg-admin-surface p-admin-card') }}
    data-admin-media-picker="{{ $mode }}"
>
    <div class="flex flex-col gap-admin-field md:flex-row md:items-start md:justify-between">
        <div>
            <h2 class="text-base font-semibold text-admin-text">Media picker</h2>
            <p class="mt-1 text-sm text-admin-muted">
                {{ $mode === 'multiple' ? 'Multiple selection' : 'Single selection' }}
                @if (count($acceptedTypes) > 0)
                    · {{ implode(', ', $acceptedTypes) }}
                @endif
            </p>
        </div>

        <div class="flex flex-wrap gap-admin-field">
            <button type="button" disabled class="rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm font-medium text-admin-muted">
                Choose
            </button>
            <button type="button" disabled class="rounded-admin-input bg-admin-primary px-3 py-2 text-sm font-medium text-white opacity-60">
                Upload
            </button>
        </div>
    </div>

    <div class="mt-4">
        @if (! $hasItems)
            <x-admin.empty-state :title="$emptyTitle" :description="$emptyDescription" icon="+" />
        @else
            <div class="grid gap-admin-field sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($selectedItems as $item)
                    @php
                        $name = $item['name'] ?? $item['label'] ?? 'Selected media';
                        $type = $item['type'] ?? 'image';
                        $url = $item['url'] ?? null;
                    @endphp

                    <article class="overflow-hidden rounded-admin-card border border-admin-border bg-admin-surface-muted">
                        <div class="flex aspect-video items-center justify-center bg-admin-text text-sm font-semibold text-white">
                            @if ($url)
                                <img
                                    src="{{ $url }}"
                                    alt="{{ $name }}"
                                    loading="lazy"
                                    onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');"
                                    class="h-full w-full object-cover"
                                >
                                <span class="hidden">{{ strtoupper(substr($type, 0, 1)) }}</span>
                            @else
                                {{ strtoupper(substr($type, 0, 1)) }}
                            @endif
                        </div>

                        <div class="p-3">
                            <p class="truncate text-sm font-semibold text-admin-text">{{ $name }}</p>
                            <p class="mt-1 text-xs text-admin-muted">{{ $type }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
