<?php

namespace App\Models;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $schema_version
 * @property int $schema_revision
 * @property array<string, mixed>|null $seo_json
 */
#[Fillable([
    'schema_version', 'schema_revision',
    'site_id', 'locale', 'central_category_id', 'central_category_version', 'parent_category_id',
    'slug', 'title', 'status', 'payload_json', 'seo_json', 'facets_json', 'comparison_json',
    'checksum', 'built_at', 'stale_at', 'failed_at', 'failure_reason',
])]
final class SiteCategoryProjection extends Model
{
    protected $attributes = ['schema_version' => 1, 'schema_revision' => 0];

    protected function casts(): array
    {
        return [
            'schema_version' => 'integer',
            'schema_revision' => 'integer',
            'central_category_version' => 'integer',
            'status' => ProjectionStatus::class,
            'payload_json' => 'array',
            'seo_json' => 'array',
            'facets_json' => 'array',
            'comparison_json' => 'array',
            'built_at' => 'datetime',
            'stale_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return BelongsTo<CentralCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CentralCategory::class, 'central_category_id');
    }
}
