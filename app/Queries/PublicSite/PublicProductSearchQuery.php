<?php

namespace App\Queries\PublicSite;

use App\Contracts\Persistence\RawSqlPersistenceBoundary;
use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use Illuminate\Database\Eloquent\Collection;

final class PublicProductSearchQuery implements RawSqlPersistenceBoundary
{
    /** @return Collection<int, SiteSearchDocument> */
    public function search(Site $site, string $locale, string $term, int $limit = 24): Collection
    {
        $escapedTerm = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term));
        $pattern = "%{$escapedTerm}%";

        return SiteSearchDocument::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where('document_type', 'product')
            ->where('status', ProjectionStatus::Active)
            ->where(function ($query) use ($pattern): void {
                $query->whereRaw("LOWER(search_text) LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("LOWER(title) LIKE ? ESCAPE '!'", [$pattern]);
            })
            ->orderBy('title')
            ->limit(max(1, min($limit, 100)))
            ->get();
    }
}
