<?php

namespace App\Queries\Media;

use App\Data\Media\ProductMediaManagerData;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;

final class ProductMediaManagerQuery
{
    public function forProduct(CentralProduct $product, string $assetSearch): ProductMediaManagerData
    {
        $assignments = MediaAssignment::query()
            ->with('asset.variants')
            ->forEntity(MediaAssignment::ENTITY_TYPE_CENTRAL_PRODUCT, (int) $product->getKey())
            ->orderBy('role')
            ->orderBy('position')
            ->get()
            ->groupBy('role')
            ->toBase();

        $assets = MediaAsset::query()
            ->when($assetSearch !== '', function ($query) use ($assetSearch): void {
                $query->where(function ($query) use ($assetSearch): void {
                    $query->where('original_filename', 'like', "%{$assetSearch}%")
                        ->orWhere('checksum', 'like', "%{$assetSearch}%");

                    if (ctype_digit($assetSearch)) {
                        $query->orWhere('id', (int) $assetSearch);
                    }
                });
            })
            ->latest()
            ->limit(50)
            ->get();

        return new ProductMediaManagerData($assignments, $assets, $assetSearch);
    }
}
