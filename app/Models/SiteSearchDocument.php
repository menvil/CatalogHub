<?php

namespace App\Models;

use App\Domains\Projections\Enums\ProjectionStatus;
use Database\Factories\SiteSearchDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $filter_values_json
 * @property array<string, mixed>|null $sort_values_json
 * @property array<string, mixed>|null $payload_json
 */
#[Fillable([
    'site_id', 'locale', 'document_type', 'document_id', 'title', 'slug', 'status', 'search_text', 'min_price', 'max_price',
    'offers_count',
    'in_stock',
    'last_price_update_at',
    'filter_values_json', 'sort_values_json', 'payload_json', 'checksum', 'built_at', 'stale_at',
])]
final class SiteSearchDocument extends Model
{
    /** @use HasFactory<SiteSearchDocumentFactory> */
    use HasFactory;

    protected static function newFactory(): SiteSearchDocumentFactory
    {
        return SiteSearchDocumentFactory::new();
    }

    protected function casts(): array
    {
        return [
            'document_id' => 'integer',
            'status' => ProjectionStatus::class,
            'min_price' => 'decimal:2',
            'max_price' => 'decimal:2',
            'offers_count' => 'integer',
            'in_stock' => 'boolean',
            'last_price_update_at' => 'datetime',
            'filter_values_json' => 'array',
            'sort_values_json' => 'array',
            'payload_json' => 'array',
            'built_at' => 'datetime',
            'stale_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
