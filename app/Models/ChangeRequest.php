<?php

namespace App\Models;

use App\Enums\ChangeRequestStatus;
use App\Models\CentralCatalog\CentralProduct;
use Database\Factories\ChangeRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ChangeRequestStatus $status
 * @property mixed $old_value_json
 * @property mixed $proposed_value_json
 * @property array<string, mixed>|null $metadata_json
 */
#[Fillable([
    'site_id',
    'central_product_id',
    'entity_type',
    'entity_id',
    'field_path',
    'old_value_json',
    'proposed_value_json',
    'evidence_url',
    'evidence_note',
    'status',
    'created_by_user_id',
    'reviewed_by_user_id',
    'applied_by_user_id',
    'reviewed_at',
    'applied_at',
    'rejection_reason',
    'metadata_json',
])]
final class ChangeRequest extends Model
{
    /** @use HasFactory<ChangeRequestFactory> */
    use HasFactory;

    protected $table = 'central_change_requests';

    protected static function newFactory(): ChangeRequestFactory
    {
        return ChangeRequestFactory::new();
    }

    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'old_value_json' => 'array',
            'proposed_value_json' => 'array',
            'metadata_json' => 'array',
            'status' => ChangeRequestStatus::class,
            'reviewed_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
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

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by_user_id');
    }
}
