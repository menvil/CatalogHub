<?php

namespace App\Services\Export;

use App\Models\CatalogSnapshot;
use App\Models\CentralCatalog\CentralProduct;

final class ProductsJsonlExporter
{
    public function __construct(private readonly JsonlStreamWriter $writer) {}

    public function export(CatalogSnapshot $snapshot): JsonlExportResult
    {
        $rows = CentralProduct::query()
            ->orderBy('id')
            ->cursor()
            ->map(fn (CentralProduct $product): array => [
                'id' => $product->getKey(),
                'slug' => $product->slug,
                'brand_id' => $product->central_brand_id,
                'category_id' => $product->central_category_id,
                'name' => $product->name,
                'model' => $product->model,
                'status' => $product->status->value,
                'version' => $product->version,
                'created_at' => $product->created_at?->toISOString(),
                'updated_at' => $product->updated_at?->toISOString(),
            ]);

        return $this->writer->write($snapshot, 'products', $rows);
    }
}
