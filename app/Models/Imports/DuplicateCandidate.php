<?php

namespace App\Models\Imports;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'import_batch_id',
    'normalized_product_draft_id',
    'candidate_type',
    'candidate_id',
    'score',
    'reason_json',
    'status',
    'reviewed_by_user_id',
    'reviewed_at',
])]
final class DuplicateCandidate extends Model
{
    protected function casts(): array
    {
        return [
            'candidate_id' => 'integer',
            'score' => 'decimal:4',
            'reason_json' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ImportBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    /** @return BelongsTo<NormalizedProductDraft, $this> */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(NormalizedProductDraft::class, 'normalized_product_draft_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
