<?php

namespace App\Models\CentralCatalog;

use App\Enums\CentralCategoryStatus;
use Database\Factories\CentralCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CentralCategoryStatus $status
 */
#[Fillable(['parent_id', 'name', 'slug', 'status', 'position'])]
final class CentralCategory extends Model
{
    /** @use HasFactory<CentralCategoryFactory> */
    use HasFactory;

    protected $table = 'central_categories';

    protected static function newFactory(): CentralCategoryFactory
    {
        return CentralCategoryFactory::new();
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'status' => CentralCategoryStatus::class,
        ];
    }

    /**
     * @return BelongsTo<CentralCategory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<CentralCategory, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<CentralProduct, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(CentralProduct::class, 'central_category_id');
    }

    /**
     * @return list<int>
     */
    public function descendantIds(): array
    {
        if (! $this->exists) {
            return [];
        }

        $descendantIds = [];
        $parentIds = [$this->getKey()];

        while ($parentIds !== []) {
            $childIds = self::query()
                ->whereIn('parent_id', $parentIds)
                ->pluck($this->getKeyName())
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $childIds = array_values(array_diff($childIds, $descendantIds));

            if ($childIds === []) {
                break;
            }

            $descendantIds = array_values(array_unique([...$descendantIds, ...$childIds]));
            $parentIds = $childIds;
        }

        return $descendantIds;
    }
}
