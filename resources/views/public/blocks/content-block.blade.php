@php($items = collect($data['items'] ?? []))

@if ($items->isNotEmpty())
    <section data-theme-block="content_block" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-600">Editorial</p>
        <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">{{ $config['title'] ?? 'Latest guides and articles' }}</h2>

        <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($items as $item)
                <a href="{{ $item->url }}" class="group rounded-2xl border border-slate-200 p-5 transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-blue-600">{{ $item->typeLabel }}</p>
                    <h3 class="mt-2 text-lg font-semibold text-slate-950 group-hover:text-blue-600">{{ $item->title }}</h3>
                    @if (($config['show_excerpt'] ?? true) && filled($item->excerpt))
                        <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">{{ $item->excerpt }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    </section>
@endif
