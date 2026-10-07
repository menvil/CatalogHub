<?php

namespace App\Models;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\CentralCatalog\CentralProduct;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $schema_version
 * @property int $schema_revision
 * @property ProjectionStatus $status
 * @property array<string, mixed> $payload_json
 * @property array<string, mixed>|null $seo_json
 */
#[Fillable([
    'schema_version', 'schema_revision',
    'site_id', 'locale', 'central_product_id', 'central_product_version', 'slug', 'canonical_url',
    'title', 'status', 'payload_json', 'seo_json', 'media_json', 'search_summary_json', 'checksum',
    'built_at', 'stale_at', 'failed_at', 'failure_reason',
])]
final class SiteProductProjection extends Model
{
    protected $attributes = ['schema_version' => 1, 'schema_revision' => 0];

    protected function casts(): array
    {
        return [
            'schema_version' => 'integer',
            'schema_revision' => 'integer',
            'central_product_version' => 'integer',
            'status' => ProjectionStatus::class,
            'payload_json' => 'array',
            'seo_json' => 'array',
            'media_json' => 'array',
            'search_summary_json' => 'array',
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

    /** @return BelongsTo<CentralProduct, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(CentralProduct::class, 'central_product_id');
    }
}
