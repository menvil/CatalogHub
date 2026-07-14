@if ($items->isNotEmpty())
    <section aria-labelledby="related-content-heading" class="mt-12">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-600">Learn more</p>
                <h2 id="related-content-heading" class="mt-2 text-2xl font-bold tracking-tight text-slate-950">
                    Related guides and articles
                </h2>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            @foreach ($items as $item)
                <a href="{{ $item->url }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-[0.15em] text-blue-600">{{ $item->typeLabel }}</p>
                    <h3 class="mt-2 text-lg font-semibold text-slate-950 group-hover:text-blue-600">{{ $item->title }}</h3>
                    @if (filled($item->excerpt))
                        <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">{{ $item->excerpt }}</p>
                    @endif
                    @if ($item->publishedDate)
                        <p class="mt-4 text-xs text-slate-500">{{ $item->publishedDate }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    </section>
@endif
