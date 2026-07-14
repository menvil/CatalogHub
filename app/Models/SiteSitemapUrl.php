<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'site_id', 'locale', 'url', 'entity_type', 'entity_id', 'changefreq', 'priority', 'lastmod_at',
    'status', 'checksum',
])]
final class SiteSitemapUrl extends Model
{
    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'priority' => 'float',
            'lastmod_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
