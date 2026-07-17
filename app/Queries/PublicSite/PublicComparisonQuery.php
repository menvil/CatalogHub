<?php

namespace App\Queries\PublicSite;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\Site;
use App\Models\SiteProductProjection;
use Illuminate\Support\Collection;

final class PublicComparisonQuery
{
    /**
     * @param  list<string>  $slugs
     * @return Collection<int, SiteProductProjection>
     */
    public function findActiveInOrder(Site $site, string $locale, array $slugs): Collection
    {
        $available = SiteProductProjection::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where('status', ProjectionStatus::Active)
            ->whereIn('slug', $slugs)
            ->get()
            ->keyBy('slug');

        return collect($slugs)
            ->map(fn (string $slug): ?SiteProductProjection => $available->get($slug))
            ->filter(fn (?SiteProductProjection $projection): bool => $projection instanceof SiteProductProjection)
            ->values();
    }
}
