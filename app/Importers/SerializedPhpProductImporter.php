<?php

namespace App\Importers;

use App\Contracts\Imports\ProductImporterInterface;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Models\Imports\RawProduct;
use Illuminate\Http\UploadedFile;
use JsonException;
use RuntimeException;

final class SerializedPhpProductImporter implements ProductImporterInterface
{
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
        $imported = 0;
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
                $this->persistRawProduct($batch, $product, $position + 1);
                $imported++;
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

        $batch->forceFill([
            'total_items' => count($products),
            'raw_items_count' => $batch->raw_items_count + $imported,
            'failed_count' => $batch->failed_count + $failed,
        ])->save();
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

    /**
     * @param  array<array-key, mixed>  $payload
     *
     * @throws JsonException
     */
    private function persistRawProduct(ImportBatch $batch, array $payload, int $rowNumber): RawProduct
    {
        $encodedPayload = json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        return RawProduct::query()->create([
            'import_batch_id' => $batch->id,
            'import_source_id' => $batch->import_source_id,
            'external_id' => $this->firstScalar($payload, ['external_id', 'id', 'sku']),
            'source_row_number' => $rowNumber,
            'raw_title' => $this->firstScalar($payload, ['title', 'name', 'product_name']),
            'raw_brand' => $this->firstScalar($payload, ['brand', 'manufacturer']),
            'raw_category' => $this->firstScalar($payload, ['category', 'category_name']),
            'raw_payload_json' => $payload,
            'payload_hash' => hash('sha256', $encodedPayload),
            'status' => 'pending',
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @param  list<string>  $keys
     */
    private function firstScalar(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload) && is_scalar($payload[$key])) {
                return (string) $payload[$key];
            }
        }

        return null;
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
