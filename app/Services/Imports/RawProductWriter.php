<?php

namespace App\Services\Imports;

use App\Models\Imports\ImportBatch;
use App\Models\Imports\RawProduct;
use JsonException;

final class RawProductWriter
{
    private readonly RawPayloadHasher $payloadHasher;

    public function __construct(?RawPayloadHasher $payloadHasher = null)
    {
        $this->payloadHasher = $payloadHasher ?? new RawPayloadHasher;
    }

    /**
     * @param  array<array-key, mixed>  $payload
     *
     * @throws JsonException
     */
    public function write(ImportBatch $batch, array $payload, ?int $sourceRowNumber = null): RawProduct
    {
        $rawProduct = RawProduct::query()->create([
            'import_batch_id' => $batch->id,
            'import_source_id' => $batch->import_source_id,
            'external_id' => $this->firstScalar($payload, ['external_id', 'id', 'sku']),
            'source_row_number' => $sourceRowNumber,
            'raw_title' => $this->firstScalar($payload, ['title', 'name', 'product_name']),
            'raw_brand' => $this->firstScalar($payload, ['brand', 'manufacturer']),
            'raw_category' => $this->firstScalar($payload, ['category', 'category_name']),
            'raw_payload_json' => $payload,
            'payload_hash' => $this->payloadHasher->hash($payload),
            'status' => 'pending',
        ]);

        $batch->increment('raw_items_count');

        return $rawProduct;
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
