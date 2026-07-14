<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="review-form-title">
    <h2 id="review-form-title" class="text-2xl font-bold tracking-tight text-slate-950">Leave a review</h2>
    <p class="mt-2 text-sm text-slate-600">Share your experience. Reviews are published after moderation.</p>

    <form class="mt-6 space-y-5">
        <div>
            <label for="review-author-name" class="block text-sm font-medium text-slate-800">Your name</label>
            <input id="review-author-name" type="text" wire:model="authorName" autocomplete="name"
                class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-950">
        </div>

        <div>
            <label for="review-author-email" class="block text-sm font-medium text-slate-800">Email (optional)</label>
            <input id="review-author-email" type="email" wire:model="authorEmail" autocomplete="email"
                class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-950">
        </div>

        <fieldset>
            <legend class="text-sm font-medium text-slate-800">Rating</legend>
            <div class="mt-2 flex gap-3">
                @foreach (range(1, 5) as $value)
                    <label class="inline-flex items-center gap-1 text-sm text-slate-700">
                        <input type="radio" wire:model="rating" value="{{ $value }}">
                        {{ $value }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div>
            <label for="review-comment" class="block text-sm font-medium text-slate-800">Your review</label>
            <textarea id="review-comment" wire:model="comment" rows="5"
                class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-950"></textarea>
        </div>

        <button type="button" disabled
            class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white opacity-60">
            Submit review
        </button>
    </form>
</section>
