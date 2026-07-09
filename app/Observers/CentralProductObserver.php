<?php

namespace App\Observers;

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
            $product->brand?->name,
            $product->name,
            $product->model,
        ])->filter()->implode(' ');
    }
}
