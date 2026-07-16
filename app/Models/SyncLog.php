<?php

namespace App\Models;

use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use Database\Factories\SyncLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'site_id',
    'central_product_id',
    'central_category_id',
    'operation',
    'status',
    'triggered_by',
    'triggered_by_user_id',
    'started_at',
    'finished_at',
    'affected_count',
    'error_message',
    'context_json',
])]
final class SyncLog extends Model
{
    /** @use HasFactory<SyncLogFactory> */
    use HasFactory;

    protected static function newFactory(): SyncLogFactory
    {
        return SyncLogFactory::new();
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'affected_count' => 'integer',
            'context_json' => 'array',
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

    /** @return BelongsTo<CentralCategory, $this> */
    public function centralCategory(): BelongsTo
    {
        return $this->belongsTo(CentralCategory::class);
    }

    /** @return BelongsTo<User, $this> */
    public function triggeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}
