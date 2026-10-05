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
 * @property int $schema_revision
 * @property int|null $schema_reviewed_revision
 * @property int|null $schema_approved_revision
 */
#[Fillable(['parent_id', 'name', 'slug', 'status', 'schema_status', 'position'])]
final class CentralCategory extends Model
{
    /** @use HasFactory<CentralCategoryFactory> */
    use HasFactory;

    protected $table = 'central_categories';

    protected $attributes = ['schema_revision' => 1];

    protected static function newFactory(): CentralCategoryFactory
    {
        return CentralCategoryFactory::new();
    }

    /** @return HasMany<CategoryAttributeAssignment, $this> */
    public function attributeAssignments(): HasMany
    {
        return $this->hasMany(CategoryAttributeAssignment::class, 'central_category_id');
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'parent_id' => 'integer',
            'schema_revision' => 'integer',
            'schema_reviewed_revision' => 'integer',
            'schema_approved_revision' => 'integer',
            'schema_reviewed_by_user_id' => 'integer',
            'schema_approved_by_user_id' => 'integer',
            'schema_reviewed_at' => 'datetime',
            'schema_approved_at' => 'datetime',
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
