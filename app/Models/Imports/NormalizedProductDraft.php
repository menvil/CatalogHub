<?php

namespace App\Models\Imports;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\User;
use Database\Factories\NormalizedProductDraftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'import_batch_id',
    'raw_product_id',
    'matched_central_product_id',
    'brand_id',
    'category_id',
    'title',
    'slug',
    'normalized_payload_json',
    'attributes_json',
    'media_json',
    'confidence',
    'status',
    'review_notes',
    'approved_by_user_id',
    'approved_at',
])]
final class NormalizedProductDraft extends Model
{
    /** @use HasFactory<NormalizedProductDraftFactory> */
    use HasFactory;

    protected static function newFactory(): NormalizedProductDraftFactory
    {
        return NormalizedProductDraftFactory::new();
    }

    protected function casts(): array
    {
        return [
            'normalized_payload_json' => 'array',
            'attributes_json' => 'array',
            'media_json' => 'array',
            'confidence' => 'decimal:4',
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<RawProduct, $this> */
    public function rawProduct(): BelongsTo
    {
        return $this->belongsTo(RawProduct::class);
    }

    /** @return BelongsTo<ImportBatch, $this> */
    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    /** @return BelongsTo<CentralBrand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(CentralBrand::class, 'brand_id');
    }

    /** @return BelongsTo<CentralCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CentralCategory::class, 'category_id');
    }

    /** @return BelongsTo<CentralProduct, $this> */
    public function matchedCentralProduct(): BelongsTo
    {
        return $this->belongsTo(CentralProduct::class, 'matched_central_product_id');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /** @return HasMany<DuplicateCandidate, $this> */
    public function duplicateCandidates(): HasMany
    {
        return $this->hasMany(DuplicateCandidate::class);
    }

    /** @return HasMany<NormalizationError, $this> */
    public function errors(): HasMany
    {
        return $this->hasMany(NormalizationError::class);
    }
}
