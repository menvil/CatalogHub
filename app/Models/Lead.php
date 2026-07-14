<?php

namespace App\Models;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property LeadType $type
 * @property LeadStatus $status
 * @property array<string, mixed>|null $metadata
 */
#[Fillable([
    'site_id',
    'central_product_id',
    'central_category_id',
    'type',
    'status',
    'name',
    'email',
    'phone',
    'city',
    'message',
    'locale',
    'source',
    'consent_accepted_at',
    'metadata',
])]
final class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    protected static function newFactory(): LeadFactory
    {
        return LeadFactory::new();
    }

    protected function casts(): array
    {
        return [
            'type' => LeadType::class,
            'status' => LeadStatus::class,
            'consent_accepted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @param Builder<Lead> $query */
    public function scopeForSite(Builder $query, Site|int $site): Builder
    {
        return $query->where('site_id', $site instanceof Site ? $site->getKey() : $site);
    }

    /** @param Builder<Lead> $query */
    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', LeadStatus::New);
    }

    /** @param Builder<Lead> $query */
    public function scopeNotSpam(Builder $query): Builder
    {
        return $query->where('status', '!=', LeadStatus::Spam);
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return BelongsTo<CentralProduct, $this> */
    public function centralProduct(): BelongsTo
    {
        return $this->belongsTo(CentralProduct::class);
    }

    /** @return BelongsTo<CentralCategory, $this> */
    public function centralCategory(): BelongsTo
    {
        return $this->belongsTo(CentralCategory::class);
    }
}
