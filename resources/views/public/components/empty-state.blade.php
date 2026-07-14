<section data-public-empty-state class="mt-8 rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm">
    <span aria-hidden="true" class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-2xl text-slate-400">◇</span>
    <h2 class="mt-5 text-xl font-bold tracking-tight">{{ $title ?? 'Nothing to show yet' }}</h2>
    @if (filled($message ?? null))
        <p class="mx-auto mt-2 max-w-xl text-slate-600">{{ $message }}</p>
    @endif
    @if (filled($actionUrl ?? null) && filled($actionLabel ?? null))
        <a href="{{ $actionUrl }}" class="mt-6 inline-flex rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white transition hover:bg-blue-500">
            {{ $actionLabel }}
        </a>
    @endif
</section>
