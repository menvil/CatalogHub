<?php

namespace App\Queries\PublicSite;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\Site;
use App\Models\SiteCategoryProjection;

final class PublicCategoryQuery
{
    public function findActive(Site $site, string $locale, string $slug): SiteCategoryProjection
    {
        return SiteCategoryProjection::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('status', ProjectionStatus::Active)
            ->firstOrFail();
    }
}
