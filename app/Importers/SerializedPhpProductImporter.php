<?php

namespace App\Importers;

use App\Contracts\Imports\ProductImporterInterface;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Services\Imports\RawProductWriter;
use Illuminate\Http\UploadedFile;
use JsonException;
use RuntimeException;

final class SerializedPhpProductImporter implements ProductImporterInterface
{
    private const int DEFAULT_MAX_ARTIFACT_BYTES = 50 * 1024 * 1024;

    private const int DEFAULT_MAX_DEPTH = 64;

    private readonly RawProductWriter $writer;

    public function __construct(?RawProductWriter $writer = null)
    {
        $this->writer = $writer ?? new RawProductWriter;
    }

    public function supports(ImportSource $source): bool
    {
        return $source->isSerializedPhp();
    }

    /** @param array<string, mixed> $options */
    public function import(ImportBatch $batch, UploadedFile|string $artifact, array $options = []): void
    {
        $contents = $this->readArtifact($artifact);
        $payload = @unserialize($contents, [
            'allowed_classes' => false,
            'max_depth' => max(1, (int) config('imports.serialized_php_max_depth', self::DEFAULT_MAX_DEPTH)),
        ]);

        if (! is_array($payload)) {
            $this->recordError(
                $batch,
                'invalid_serialized_payload',
                'The import artifact does not contain a serialized product array.'
            );
            $batch->increment('failed_count');

            return;
        }

        $products = $this->extractProducts($payload);
        $failed = 0;

        foreach ($products as $position => $product) {
            if (! is_array($product)) {
                $failed++;
                $this->recordError(
                    $batch,
                    'invalid_product_payload',
                    'A serialized product item must be an array.',
                    ['position' => $position]
                );

                continue;
            }

            try {
                $this->writer->write($batch, $product, $position + 1);
            } catch (JsonException $exception) {
                $failed++;
                $this->recordError(
                    $batch,
                    'invalid_product_json',
                    $exception->getMessage(),
                    ['position' => $position]
                );
            }
        }

        $batch->update(['total_items' => count($products)]);

        if ($failed > 0) {
            $batch->increment('failed_count', $failed);
        }
    }

    private function readArtifact(UploadedFile|string $artifact): string
    {
        $path = $artifact instanceof UploadedFile ? $artifact->getRealPath() : $artifact;
        $maxBytes = max(1, (int) config('imports.serialized_php_max_bytes', self::DEFAULT_MAX_ARTIFACT_BYTES));

        if ($path === false || ! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('The serialized import artifact could not be read.');
        }

        $fileSize = filesize($path);

        if ($fileSize === false || $fileSize > $maxBytes) {
            throw new RuntimeException('The serialized import artifact exceeds the configured size limit.');
        }

        $contents = file_get_contents($path, false, null, 0, $maxBytes + 1);

        if ($contents === false) {
            throw new RuntimeException('The serialized import artifact could not be read.');
        }

        if (strlen($contents) > $maxBytes) {
            throw new RuntimeException('The serialized import artifact exceeds the configured size limit.');
        }

        return $contents;
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return list<mixed>
     */
    private function extractProducts(array $payload): array
    {
        if (isset($payload['products']) && is_array($payload['products'])) {
            return array_values($payload['products']);
        }

        return array_is_list($payload) ? $payload : [$payload];
    }

    /** @param array<string, mixed> $context */
    private function recordError(
        ImportBatch $batch,
        string $code,
        string $message,
        array $context = [],
    ): void {
        $batch->errors()->create([
            'severity' => 'error',
            'code' => $code,
            'message' => $message,
            'context_json' => $context,
        ]);
    }
}
