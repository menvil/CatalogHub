<?php

namespace App\Services\Imports;

use App\Models\Imports\ImportBatch;
use App\Models\Imports\RawProduct;
use JsonException;

final class RawProductWriter
{
    private const int MAX_PAYLOAD_DEPTH = 64;

    /**
     * @param  array<array-key, mixed>  $payload
     *
     * @throws JsonException
     */
    public function write(ImportBatch $batch, array $payload, ?int $sourceRowNumber = null): RawProduct
    {
        // Validate recursion and depth before canonicalize() traverses untrusted legacy data.
        json_encode($payload, JSON_THROW_ON_ERROR, self::MAX_PAYLOAD_DEPTH);

        $canonicalPayload = json_encode(
            $this->canonicalize($payload),
            JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            self::MAX_PAYLOAD_DEPTH,
        );

        $rawProduct = RawProduct::query()->create([
            'import_batch_id' => $batch->id,
            'import_source_id' => $batch->import_source_id,
            'external_id' => $this->firstScalar($payload, ['external_id', 'id', 'sku']),
            'source_row_number' => $sourceRowNumber,
            'raw_title' => $this->firstScalar($payload, ['title', 'name', 'product_name']),
            'raw_brand' => $this->firstScalar($payload, ['brand', 'manufacturer']),
            'raw_category' => $this->firstScalar($payload, ['category', 'category_name']),
            'raw_payload_json' => $payload,
            'payload_hash' => hash('sha256', $canonicalPayload),
            'status' => 'pending',
        ]);

        $batch->increment('raw_items_count');

        return $rawProduct;
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map($this->canonicalize(...), $value);
        }

        ksort($value, SORT_STRING);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @param  list<string>  $keys
     */
    private function firstScalar(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload) && is_scalar($payload[$key])) {
                return mb_substr((string) $payload[$key], 0, 255);
            }
        }

        return null;
    }
}
