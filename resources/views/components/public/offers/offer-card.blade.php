<article data-offer-card class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-start justify-between gap-4">
        <div class="flex min-w-0 items-center gap-3">
            @if ($merchantLogoUrl)
                <img src="{{ $merchantLogoUrl }}" alt="" class="size-11 rounded-xl border border-slate-200 object-contain p-1">
            @else
                <span data-merchant-logo-fallback aria-hidden="true" class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 font-bold text-slate-600">{{ $merchantInitial }}</span>
            @endif
            <div class="min-w-0">
                <h3 class="truncate font-semibold text-slate-950">{{ $offer->merchant->name }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ str($offer->availability->value)->headline() }}</p>
            </div>
        </div>
        <p class="shrink-0 text-xl font-bold tabular-nums text-slate-950">{{ $formattedPrice }}</p>
    </div>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <div class="space-y-2">
            <x-public.price-freshness-badge :status="$freshness" />
            <p class="text-xs text-slate-500">{{ $deliverySummary }}</p>
        </div>

        @if ($actionUrl)
            <a href="{{ $actionUrl }}" rel="nofollow sponsored" class="inline-flex rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white transition hover:bg-blue-500">Go to shop</a>
        @else
            <span aria-disabled="true" class="inline-flex cursor-not-allowed rounded-lg bg-slate-200 px-4 py-2 font-semibold text-slate-500">Go to shop</span>
        @endif
    </div>
</article>
