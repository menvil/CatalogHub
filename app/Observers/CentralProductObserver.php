<?php

namespace App\Observers;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralProduct;
use App\Services\Slugs\UniqueSlugGenerator;

final class CentralProductObserver
{
    public function __construct(
        private readonly UniqueSlugGenerator $slugGenerator,
    ) {}

    public function saving(CentralProduct $product): void
    {
        if (filled($product->slug)) {
            return;
        }

        $product->slug = $this->slugGenerator->generate(
            $this->slugSource($product),
            CentralProduct::class,
            ignore: $product,
        );
    }

    private function slugSource(CentralProduct $product): string
    {
        return collect([
            $this->brandName($product),
            $product->name,
            $product->model,
        ])->filter()->implode(' ');
    }

    private function brandName(CentralProduct $product): ?string
    {
        $brandId = $product->central_brand_id;

        if (blank($brandId)) {
            return null;
        }

        if (
            $product->relationLoaded('brand') &&
            $product->brand?->getKey() === $brandId
        ) {
            return $product->brand->name;
        }

        return CentralBrand::query()->whereKey($brandId)->value('name');
    }
}
