<?php

namespace App\Queries\PublicSite;

use App\Models\ContentTranslation;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;

final class PublishedContentQuery
{
    public function find(Site $site, string $locale, string $slug): ?ContentTranslation
    {
        return ContentTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereHas('contentItem', fn (Builder $query): Builder => $query
                ->where('site_id', $site->id)
                ->where('status', 'published'))
            ->with('contentItem')
            ->first();
    }
}
