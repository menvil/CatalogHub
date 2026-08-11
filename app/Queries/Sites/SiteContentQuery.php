<?php

declare(strict_types=1);

namespace App\Queries\Sites;

use App\Models\ContentItem;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;

final class SiteContentQuery
{
    /** @return Builder<ContentItem> */
    public function forSite(Site $site): Builder
    {
        return ContentItem::query()->forSite($site);
    }

    public function find(Site $site, int $contentItemId): ?ContentItem
    {
        return $this->forSite($site)->find($contentItemId);
    }
}
