<?php

namespace App\Models\Imports;

use Database\Factories\RawProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'import_batch_id',
    'import_source_id',
    'external_id',
    'source_row_number',
    'raw_title',
    'raw_brand',
    'raw_category',
    'raw_payload_json',
    'payload_hash',
    'status',
    'error_message',
])]
final class RawProduct extends Model
{
    /** @use HasFactory<RawProductFactory> */
    use HasFactory;

    protected static function newFactory(): RawProductFactory
    {
        return RawProductFactory::new();
    }

    protected function casts(): array
    {
        return [
            'source_row_number' => 'integer',
            'raw_payload_json' => 'array',
        ];
    }

    /** @return BelongsTo<ImportBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    /** @return BelongsTo<ImportSource, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(ImportSource::class, 'import_source_id');
    }

    /** @return HasOne<NormalizedProductDraft, $this> */
    public function draft(): HasOne
    {
        return $this->hasOne(NormalizedProductDraft::class);
    }

    /** @return HasMany<NormalizationError, $this> */
    public function errors(): HasMany
    {
        return $this->hasMany(NormalizationError::class);
    }
}
