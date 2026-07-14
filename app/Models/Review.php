<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Models\CentralCatalog\CentralProduct;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ReviewStatus $status
 * @property array<string, mixed>|null $metadata
 */
#[Fillable([
    'site_id',
    'central_product_id',
    'author_name',
    'author_email',
    'rating',
    'pros',
    'cons',
    'comment',
    'status',
    'locale',
    'approved_at',
    'rejected_at',
    'spam_marked_at',
    'metadata',
])]
final class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected static function newFactory(): ReviewFactory
    {
        return ReviewFactory::new();
    }

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ReviewStatus::class,
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'spam_marked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @param Builder<Review> $query */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::Approved);
    }

    /** @param Builder<Review> $query */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::Pending);
    }

    /** @param Builder<Review> $query */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::Rejected);
    }

    /** @param Builder<Review> $query */
    public function scopeSpam(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::Spam);
    }

    /** @param Builder<Review> $query */
    public function scopeVisiblePublicly(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::Approved);
    }

    /** @param Builder<Review> $query */
    public function scopeForSite(Builder $query, Site|int $site): Builder
    {
        return $query->where('site_id', $site instanceof Site ? $site->getKey() : $site);
    }

    /** @param Builder<Review> $query */
    public function scopeForProduct(Builder $query, CentralProduct|int $product): Builder
    {
        return $query->where('central_product_id', $product instanceof CentralProduct ? $product->getKey() : $product);
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
}
