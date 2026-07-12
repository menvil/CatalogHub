<?php

namespace App\Services\Imports;

use App\Contracts\Imports\ProductImporterInterface;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Throwable;

final readonly class ImportService
{
    /**
     * @param  iterable<ProductImporterInterface>  $importers
     */
    public function __construct(private iterable $importers = []) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function startImport(
        ImportSource $source,
        UploadedFile|string $artifact,
        array $options = [],
    ): ImportBatch {
        $batch = $source->batches()->create([
            'status' => 'pending',
            'original_filename' => $this->originalFilename($artifact),
            'metadata_json' => $options,
        ]);

        try {
            $batch->markStarted();
            $this->resolveImporter($source)->import($batch, $artifact, $options);
            $batch->markFinished();
        } catch (Throwable $exception) {
            $batch->markFailed($exception->getMessage() ?: $exception::class);

            throw $exception;
        }

        return $batch->refresh();
    }

    private function resolveImporter(ImportSource $source): ProductImporterInterface
    {
        foreach ($this->importers as $importer) {
            if ($importer->supports($source)) {
                return $importer;
            }
        }

        throw new RuntimeException("No product importer supports source [{$source->code}].");
    }

    private function originalFilename(UploadedFile|string $artifact): string
    {
        return $artifact instanceof UploadedFile
            ? $artifact->getClientOriginalName()
            : basename($artifact);
    }
}
