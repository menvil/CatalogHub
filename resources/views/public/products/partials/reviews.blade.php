<section class="mt-12" aria-labelledby="customer-reviews-title">
    <div class="flex items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-600">Community feedback</p>
            <h2 id="customer-reviews-title" class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Customer reviews</h2>
        </div>
        @if ($reviews->isNotEmpty())
            <p class="text-sm text-slate-500">{{ $reviews->count() }} {{ Str::plural('review', $reviews->count()) }}</p>
        @endif
    </div>

    @if ($reviews->isEmpty())
        <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
            <h3 class="text-lg font-semibold text-slate-950">No reviews yet</h3>
            <p class="mt-2 text-sm text-slate-600">Be the first to share a useful experience with this product.</p>
        </div>
    @else
        <div class="mt-6 grid gap-5">
            @foreach ($reviews as $review)
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-950">{{ $review->author_name }}</p>
                            <p class="mt-1 text-sm text-amber-500" aria-label="Rated {{ $review->rating }} out of 5">
                                {{ str_repeat('★', $review->rating) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $review->rating) }}</span>
                            </p>
                        </div>
                        <time class="text-sm text-slate-500" datetime="{{ $review->created_at->toDateString() }}">
                            {{ $review->created_at->toFormattedDateString() }}
                        </time>
                    </div>

                    @if (filled($review->pros))
                        <p class="mt-4 text-sm text-slate-700"><strong class="text-emerald-700">Pros:</strong> {{ $review->pros }}</p>
                    @endif
                    @if (filled($review->cons))
                        <p class="mt-2 text-sm text-slate-700"><strong class="text-red-700">Cons:</strong> {{ $review->cons }}</p>
                    @endif
                    @if (filled($review->comment))
                        <p class="mt-4 leading-7 text-slate-700">{{ $review->comment }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</section>
