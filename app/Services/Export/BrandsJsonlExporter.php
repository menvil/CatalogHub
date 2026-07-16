<?php

namespace App\Services\Export;

use App\Models\CatalogSnapshot;
use App\Models\CentralCatalog\CentralBrand;

final class BrandsJsonlExporter implements JsonlExporter
{
    public function __construct(private readonly JsonlStreamWriter $writer) {}

    public function export(CatalogSnapshot $snapshot): JsonlExportResult
    {
        $rows = CentralBrand::query()
            ->orderBy('id')
            ->cursor()
            ->map(fn (CentralBrand $brand): array => [
                'id' => $brand->getKey(),
                'slug' => $brand->slug,
                'name' => $brand->name,
                'status' => $brand->status->value,
                'created_at' => $brand->created_at?->toISOString(),
                'updated_at' => $brand->updated_at?->toISOString(),
            ]);

        return $this->writer->write($snapshot, 'brands', $rows);
    }
}
