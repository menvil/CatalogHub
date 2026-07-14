@php
    $ratingValue = is_array($rating ?? null)
        ? ($rating['value'] ?? $rating['average'] ?? $rating['rating'] ?? null)
        : null;
    $reviewCount = is_array($rating ?? null)
        ? ($rating['review_count'] ?? $rating['count'] ?? null)
        : null;
    $hasRating = is_numeric($ratingValue);
    $displayRating = $hasRating ? rtrim(rtrim(number_format((float) $ratingValue, 1, '.', ''), '0'), '.') : null;
@endphp

<div data-product-rating class="mt-4 flex flex-wrap items-center gap-3 text-sm">
    @if ($hasRating)
        <span class="font-semibold text-amber-500" role="img" aria-label="Rated {{ $displayRating }} out of 5">★★★★★</span>
        <strong class="text-slate-950">{{ $displayRating }}</strong>
        @if (is_numeric($reviewCount))
            <span class="text-slate-500">{{ (int) $reviewCount }} {{ Str::plural('review', (int) $reviewCount) }}</span>
        @endif
    @else
        <span class="text-slate-400" aria-hidden="true">☆☆☆☆☆</span>
        <span class="text-slate-500">Not rated yet</span>
    @endif
</div>
