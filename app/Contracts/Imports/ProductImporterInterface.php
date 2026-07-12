<?php

namespace App\Contracts\Imports;

use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use Illuminate\Http\UploadedFile;

interface ProductImporterInterface
{
    public function supports(ImportSource $source): bool;

    /** @param array<string, mixed> $options */
    public function import(ImportBatch $batch, UploadedFile|string $artifact, array $options = []): void;
}
