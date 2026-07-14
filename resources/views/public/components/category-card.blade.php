@php
    $image = is_array($category['image'] ?? null) ? $category['image'] : [];
    $imageUrl = is_string($image['url'] ?? null) && $image['url'] !== '' ? $image['url'] : null;
@endphp

<a data-category-card href="{{ $category['url'] ?? '#' }}" class="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="flex aspect-[16/9] items-center justify-center overflow-hidden bg-slate-100">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $image['alt'] ?? $category['title'] ?? 'Category image' }}" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <span data-category-card-placeholder aria-hidden="true" class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-2xl font-bold text-blue-600 shadow-sm">
                {{ Str::upper(Str::substr($category['title'] ?? 'C', 0, 1)) }}
            </span>
        @endif
    </div>
    <div class="flex-1 p-5">
        <h3 class="text-lg font-semibold text-slate-950 transition group-hover:text-blue-600">{{ $category['title'] ?? 'Untitled category' }}</h3>
        @if (filled($category['description'] ?? null))
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $category['description'] }}</p>
        @endif
    </div>
</a>
