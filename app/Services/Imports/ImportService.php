<?php

namespace App\Services\Imports;

use App\Contracts\Imports\ProductImporterInterface;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            $this->storeOriginalArtifact($batch, $artifact);
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

    private function storeOriginalArtifact(ImportBatch $batch, UploadedFile|string $artifact): void
    {
        $sourcePath = $artifact instanceof UploadedFile ? $artifact->getRealPath() : $artifact;
        $contents = $sourcePath !== false ? file_get_contents($sourcePath) : false;

        if ($contents === false) {
            throw new RuntimeException('The original import artifact could not be read.');
        }

        $disk = (string) config('imports.artifact_disk', 'local');
        $prefix = trim((string) config('imports.artifact_prefix', 'imports'), '/');
        $originalFilename = $this->originalFilename($artifact);
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $storedFilename = (string) Str::uuid().($extension !== '' ? ".{$extension}" : '');
        $path = implode('/', array_filter([
            $prefix,
            now()->format('Y/m/d'),
            (string) $batch->getKey(),
            $storedFilename,
        ]));

        if (! Storage::disk($disk)->put($path, $contents)) {
            throw new RuntimeException("The original import artifact could not be stored on disk [{$disk}].");
        }

        $batch->artifacts()->create([
            'type' => 'original',
            'disk' => $disk,
            'path' => $path,
            'original_filename' => $originalFilename,
            'mime_type' => $artifact instanceof UploadedFile ? $artifact->getClientMimeType() : null,
            'file_size' => strlen($contents),
            'checksum' => hash('sha256', $contents),
            'metadata_json' => [],
        ]);
    }
}
