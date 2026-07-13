<?php

namespace App\Models\Imports;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $import_batch_id
 * @property int|null $raw_product_id
 * @property int|null $normalized_product_draft_id
 * @property string $severity
 * @property string $code
 * @property string $message
 * @property array<string, mixed>|null $context_json
 */
#[Fillable([
    'import_batch_id',
    'raw_product_id',
    'normalized_product_draft_id',
    'severity',
    'code',
    'message',
    'raw_key',
    'raw_value',
    'context_json',
    'resolved_at',
    'resolved_by_user_id',
])]
final class NormalizationError extends Model
{
    protected function casts(): array
    {
        return [
            'context_json' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ImportBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    /** @return BelongsTo<RawProduct, $this> */
    public function rawProduct(): BelongsTo
    {
        return $this->belongsTo(RawProduct::class);
    }

    /** @return BelongsTo<NormalizedProductDraft, $this> */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(NormalizedProductDraft::class, 'normalized_product_draft_id');
    }

    /** @return BelongsTo<User, $this> */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
