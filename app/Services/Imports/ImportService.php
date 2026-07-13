<?php

namespace App\Services\Imports;

use App\Contracts\Imports\ProductImporterInterface;
use App\Jobs\Imports\ProcessImportBatchJob;
use App\Models\Imports\ImportArtifact;
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
    public function __construct(
        private iterable $importers = [],
        private ?ImportMediaDownloader $mediaDownloader = null,
        private ?DuplicateDetector $duplicateDetector = null,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function startImport(
        ImportSource $source,
        UploadedFile|string $artifact,
        array $options = [],
    ): ImportBatch {
        $batch = $this->createBatch($source, $artifact, $options);

        try {
            $batch->markStarted();
            $this->storeOriginalArtifact($batch, $artifact);
            $this->resolveImporter($source)->import($batch, $artifact, $options);
            $this->processDrafts($batch);
            $batch->markFinished();
        } catch (Throwable $exception) {
            $batch->markFailed($exception->getMessage() ?: $exception::class);

            throw $exception;
        }

        return $batch->refresh();
    }

    /** @param array<string, mixed> $options */
    public function queueImport(
        ImportSource $source,
        UploadedFile|string $artifact,
        array $options = [],
    ): ImportBatch {
        $batch = $this->createBatch($source, $artifact, $options);

        try {
            $this->storeOriginalArtifact($batch, $artifact);
            ProcessImportBatchJob::dispatch($batch->id);
        } catch (Throwable $exception) {
            $batch->markFailed($exception->getMessage() ?: $exception::class);

            throw $exception;
        }

        return $batch->refresh();
    }

    public function processQueuedImport(ImportBatch $batch): ImportBatch
    {
        $temporaryPath = null;

        try {
            $artifact = $batch->artifacts()->where('type', 'original')->firstOrFail();
            $temporaryPath = $this->copyArtifactToTemporaryFile($artifact);
            $batch->markStarted();
            $this->resolveImporter($batch->source)->import(
                $batch,
                $temporaryPath,
                $batch->metadata_json ?? [],
            );
            $this->processDrafts($batch);
            $batch->markFinished();
        } catch (Throwable $exception) {
            $batch->markFailed($exception->getMessage() ?: $exception::class);

            throw $exception;
        } finally {
            if (is_string($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }

        return $batch->refresh();
    }

    public function supports(ImportSource $source): bool
    {
        foreach ($this->importers as $importer) {
            if ($importer->supports($source)) {
                return true;
            }
        }

        return false;
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

    private function processDrafts(ImportBatch $batch): void
    {
        if ($this->mediaDownloader === null && $this->duplicateDetector === null) {
            return;
        }

        foreach ($batch->drafts()->lazyById() as $draft) {
            $this->mediaDownloader?->downloadForDraft($draft);
            $this->duplicateDetector?->detect($draft);
        }
    }

    private function originalFilename(UploadedFile|string $artifact): string
    {
        return $artifact instanceof UploadedFile
            ? $artifact->getClientOriginalName()
            : basename($artifact);
    }

    /** @param array<string, mixed> $options */
    private function createBatch(
        ImportSource $source,
        UploadedFile|string $artifact,
        array $options,
    ): ImportBatch {
        return $source->batches()->create([
            'status' => 'pending',
            'original_filename' => $this->originalFilename($artifact),
            'metadata_json' => $options,
        ]);
    }

    private function storeOriginalArtifact(ImportBatch $batch, UploadedFile|string $artifact): ImportArtifact
    {
        $sourcePath = $artifact instanceof UploadedFile ? $artifact->getRealPath() : $artifact;

        if (! is_string($sourcePath) || ! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new RuntimeException('The original import artifact could not be read.');
        }

        $fileSize = filesize($sourcePath);
        $checksum = hash_file('sha256', $sourcePath);

        if ($fileSize === false || $checksum === false) {
            throw new RuntimeException('The original import artifact could not be read.');
        }

        $stream = fopen($sourcePath, 'rb');

        if ($stream === false) {
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

        try {
            if (! Storage::disk($disk)->put($path, $stream)) {
                throw new RuntimeException("The original import artifact could not be stored on disk [{$disk}].");
            }
        } finally {
            fclose($stream);
        }

        return $batch->artifacts()->create([
            'type' => 'original',
            'disk' => $disk,
            'path' => $path,
            'original_filename' => $originalFilename,
            'mime_type' => $artifact instanceof UploadedFile ? $artifact->getClientMimeType() : null,
            'file_size' => $fileSize,
            'checksum' => $checksum,
            'metadata_json' => [],
        ]);
    }

    private function copyArtifactToTemporaryFile(ImportArtifact $artifact): string
    {
        $source = Storage::disk($artifact->disk)->readStream($artifact->path);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'cataloghub-import-artifact-');

        if (! is_resource($source) || $temporaryPath === false) {
            if (is_resource($source)) {
                fclose($source);
            }

            throw new RuntimeException('The stored import artifact could not be read.');
        }

        $destination = fopen($temporaryPath, 'wb');

        if ($destination === false) {
            fclose($source);
            @unlink($temporaryPath);

            throw new RuntimeException('The stored import artifact could not be read.');
        }

        try {
            if (stream_copy_to_stream($source, $destination) === false) {
                throw new RuntimeException('The stored import artifact could not be read.');
            }
        } catch (Throwable $exception) {
            @unlink($temporaryPath);

            throw $exception;
        } finally {
            fclose($source);
            fclose($destination);
        }

        return $temporaryPath;
    }
}
