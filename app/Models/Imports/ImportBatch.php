<?php

namespace App\Models\Imports;

use Database\Factories\ImportBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'import_source_id',
    'status',
    'original_filename',
    'total_items',
    'raw_items_count',
    'drafts_count',
    'approved_count',
    'rejected_count',
    'failed_count',
    'started_at',
    'finished_at',
    'error_message',
    'metadata_json',
])]
final class ImportBatch extends Model
{
    /** @use HasFactory<ImportBatchFactory> */
    use HasFactory;

    protected static function newFactory(): ImportBatchFactory
    {
        return ImportBatchFactory::new();
    }

    protected function casts(): array
    {
        return [
            'total_items' => 'integer',
            'raw_items_count' => 'integer',
            'drafts_count' => 'integer',
            'approved_count' => 'integer',
            'rejected_count' => 'integer',
            'failed_count' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'metadata_json' => 'array',
        ];
    }

    public function markStarted(): void
    {
        $this->forceFill([
            'status' => 'processing',
            'started_at' => now(),
            'finished_at' => null,
            'error_message' => null,
        ])->save();
    }

    public function markFinished(): void
    {
        $this->forceFill([
            'status' => 'completed',
            'finished_at' => now(),
            'error_message' => null,
        ])->save();
    }

    public function markFailed(string $message): void
    {
        $this->forceFill([
            'status' => 'failed',
            'finished_at' => now(),
            'error_message' => $message,
        ])->save();
    }

    /** @return BelongsTo<ImportSource, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(ImportSource::class, 'import_source_id');
    }

    /** @return HasMany<ImportArtifact, $this> */
    public function artifacts(): HasMany
    {
        return $this->hasMany(ImportArtifact::class);
    }

    /** @return HasMany<RawProduct, $this> */
    public function rawProducts(): HasMany
    {
        return $this->hasMany(RawProduct::class);
    }

    /** @return HasMany<NormalizedProductDraft, $this> */
    public function drafts(): HasMany
    {
        return $this->hasMany(NormalizedProductDraft::class);
    }

    /** @return HasMany<NormalizationError, $this> */
    public function errors(): HasMany
    {
        return $this->hasMany(NormalizationError::class);
    }
}
