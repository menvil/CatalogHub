<?php

namespace App\Models;

use App\Enums\SyncConflictStatus;
use App\Models\CentralCatalog\CentralProduct;
use Database\Factories\SyncConflictFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property SyncConflictStatus $status
 * @property mixed $central_value_json
 * @property mixed $local_value_json
 * @property array<string, mixed>|null $metadata_json
 */
#[Fillable([
    'site_id',
    'central_product_id',
    'entity_type',
    'entity_id',
    'field_path',
    'central_value_json',
    'local_value_json',
    'conflict_type',
    'status',
    'resolution',
    'resolved_by_user_id',
    'resolved_at',
    'metadata_json',
])]
final class SyncConflict extends Model
{
    /** @use HasFactory<SyncConflictFactory> */
    use HasFactory;

    protected static function newFactory(): SyncConflictFactory
    {
        return SyncConflictFactory::new();
    }

    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'central_value_json' => 'array',
            'local_value_json' => 'array',
            'status' => SyncConflictStatus::class,
            'resolved_at' => 'datetime',
            'metadata_json' => 'array',
        ];
    }

    /**
     * @param  Builder<SyncConflict>  $query
     * @return Builder<SyncConflict>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', SyncConflictStatus::Open);
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
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
