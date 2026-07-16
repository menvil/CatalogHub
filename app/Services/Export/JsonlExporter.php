<?php

namespace App\Services\Export;

use App\Models\CatalogSnapshot;

interface JsonlExporter
{
    public function export(CatalogSnapshot $snapshot): JsonlExportResult;
}
