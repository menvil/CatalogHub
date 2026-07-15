<?php

namespace App\Models;

use App\Enums\ContentRelationTargetType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use Database\Factories\ContentRelationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'content_item_id', 'related_type', 'related_id', 'relation_type', 'position', 'metadata',
])]
final class ContentRelation extends Model
{
    /** @use HasFactory<ContentRelationFactory> */
    use HasFactory;

    protected static function newFactory(): ContentRelationFactory
    {
        return ContentRelationFactory::new();
    }

    protected static function booted(): void
    {
        self::saving(function (self $relation): void {
            if ($relation->exists && ! $relation->isDirty(['related_type', 'related_id'])) {
                return;
            }

            $type = $relation->targetType();
            $targetExists = match ($type) {
                ContentRelationTargetType::Product => CentralProduct::query()->whereKey($relation->related_id)->exists(),
                ContentRelationTargetType::Category => CentralCategory::query()->whereKey($relation->related_id)->exists(),
                ContentRelationTargetType::Brand => CentralBrand::query()->whereKey($relation->related_id)->exists(),
                ContentRelationTargetType::Attribute => AttributeDefinition::query()->whereKey($relation->related_id)->exists(),
                default => true,
            };

            if (! $targetExists) {
                throw ValidationException::withMessages([
                    'related_id' => 'The selected '.($type?->label() ?? 'target').' does not exist.',
                ]);
            }
        });
    }

    private function targetType(): ?ContentRelationTargetType
    {
        $value = $this->getAttribute('related_type');

        if ($value instanceof ContentRelationTargetType) {
            return $value;
        }

        return is_string($value) ? ContentRelationTargetType::tryFrom($value) : null;
    }

    protected function casts(): array
    {
        return [
            'related_type' => ContentRelationTargetType::class,
            'related_id' => 'integer',
            'position' => 'integer',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<ContentItem, $this> */
    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }
}
