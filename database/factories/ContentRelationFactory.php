<?php

namespace Database\Factories;

use App\Enums\ContentRelationTargetType;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ContentItem;
use App\Models\ContentRelation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContentRelation> */
class ContentRelationFactory extends Factory
{
    protected $model = ContentRelation::class;

    public function definition(): array
    {
        return [
            'content_item_id' => ContentItem::factory(),
            'related_type' => ContentRelationTargetType::Product,
            'related_id' => CentralProduct::factory(),
            'relation_type' => 'related',
            'position' => 0,
            'metadata' => null,
        ];
    }

    public function product(?CentralProduct $product = null): static
    {
        return $this->state(fn (): array => [
            'related_type' => ContentRelationTargetType::Product,
            'related_id' => $product?->getKey() ?? CentralProduct::factory(),
        ]);
    }

    public function category(?CentralCategory $category = null): static
    {
        return $this->state(fn (): array => [
            'related_type' => ContentRelationTargetType::Category,
            'related_id' => $category?->getKey() ?? CentralCategory::factory(),
        ]);
    }

    public function brand(?CentralBrand $brand = null): static
    {
        return $this->state(fn (): array => [
            'related_type' => ContentRelationTargetType::Brand,
            'related_id' => $brand?->getKey() ?? CentralBrand::factory(),
        ]);
    }
}
