<?php

namespace App\Models\CentralCatalog;

use App\Enums\CategorySchemaStatus;
use App\Enums\CentralCategoryStatus;
use App\Models\Translations\CategoryTranslation;
use Database\Factories\CentralCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CentralCategoryStatus $status
 * @property CategorySchemaStatus $schema_status
 */
#[Fillable(['parent_id', 'name', 'slug', 'status', 'schema_status', 'position'])]
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
            'schema_status' => CategorySchemaStatus::class,
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
     * @return HasMany<AttributeSection, $this>
     */
    public function attributeSections(): HasMany
    {
        return $this->hasMany(AttributeSection::class, 'central_category_id');
    }

    /**
     * @return HasMany<AttributeDefinition, $this>
     */
    public function attributeDefinitions(): HasMany
    {
        return $this->hasMany(AttributeDefinition::class, 'central_category_id');
    }

    /**
     * @return HasMany<CategoryTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class, 'category_id');
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

        while (true) {
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
