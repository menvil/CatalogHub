@php
    $main = is_array($media['main'] ?? null) ? $media['main'] : null;
    $mainUrl = is_string($main['url'] ?? null) && $main['url'] !== '' ? $main['url'] : null;
    $gallery = is_array($media['gallery'] ?? null)
        ? array_values(array_filter($media['gallery'], fn ($item) => is_array($item) && filled($item['url'] ?? null)))
        : [];
@endphp

<section data-product-media-gallery aria-label="Product media gallery">
    <div class="flex aspect-square items-center justify-center overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
        @if ($mainUrl)
            <img
                src="{{ $mainUrl }}"
                alt="{{ $main['alt'] ?? 'Product image' }}"
                @if (filled($main['width'] ?? null)) width="{{ $main['width'] }}" @endif
                @if (filled($main['height'] ?? null)) height="{{ $main['height'] }}" @endif
                class="h-full w-full object-contain p-5"
            >
        @else
            <div data-media-placeholder class="flex h-full w-full flex-col items-center justify-center gap-3 p-8 text-center text-slate-400">
                <span aria-hidden="true" class="text-5xl">◇</span>
                <span class="text-sm font-medium">Image coming soon</span>
            </div>
        @endif
    </div>

    @if ($gallery !== [])
        <div class="mt-4 grid grid-cols-4 gap-3 sm:grid-cols-5" aria-label="Gallery thumbnails">
            @foreach ($gallery as $item)
                <a href="{{ $item['url'] }}" class="aspect-square overflow-hidden rounded-xl border border-slate-200 bg-white p-1 transition hover:border-blue-500">
                    <img src="{{ $item['url'] }}" alt="{{ $item['alt'] ?? 'Product gallery image' }}" loading="lazy" class="h-full w-full object-contain">
                </a>
            @endforeach
        </div>
    @endif
</section>
