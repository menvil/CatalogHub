<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property array<string, mixed>|null $config_json */
#[Fillable(['site_id', 'feature_key', 'is_enabled', 'config_json'])]
final class SiteFeature extends Model
{
    /** @var list<string> */
    public const KEYS = ['reviews', 'leads', 'price_comparison', 'comparison', 'polls', 'blog', 'guides', 'external_price_widget', 'local_offers'];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean', 'config_json' => 'array'];
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
