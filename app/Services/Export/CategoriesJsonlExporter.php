<?php

namespace App\Services\Export;

use App\Models\CatalogSnapshot;
use App\Models\CentralCatalog\CentralCategory;

final class CategoriesJsonlExporter
{
    public function __construct(private readonly JsonlStreamWriter $writer) {}

    public function export(CatalogSnapshot $snapshot): JsonlExportResult
    {
        $rows = CentralCategory::query()
            ->orderBy('id')
            ->cursor()
            ->map(fn (CentralCategory $category): array => [
                'id' => $category->getKey(),
                'slug' => $category->slug,
                'parent_id' => $category->parent_id,
                'name' => $category->name,
                'status' => $category->status->value,
                'schema_status' => $category->schema_status->value,
                'position' => $category->position,
                'created_at' => $category->created_at?->toISOString(),
                'updated_at' => $category->updated_at?->toISOString(),
            ]);

        return $this->writer->write($snapshot, 'categories', $rows);
    }
}
