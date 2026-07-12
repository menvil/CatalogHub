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
        $payload = @unserialize($contents, ['allowed_classes' => false]);

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
        $contents = $path !== false ? file_get_contents($path) : false;

        if ($contents === false) {
            throw new RuntimeException('The serialized import artifact could not be read.');
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
