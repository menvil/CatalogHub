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
        $identifier = $this->extractIdentifier($payload, ['external_id', 'id', 'sku']);

        $rawProduct = RawProduct::query()->create([
            'import_batch_id' => $batch->id,
            'import_source_id' => $batch->import_source_id,
            'external_id' => $identifier['value'],
            'source_row_number' => $sourceRowNumber,
            'raw_title' => $this->firstScalar($payload, ['title', 'name', 'product_name']),
            'raw_brand' => $this->firstScalar($payload, ['brand', 'manufacturer']),
            'raw_category' => $this->firstScalar($payload, ['category', 'category_name']),
            'raw_payload_json' => $payload,
            'payload_hash' => $this->payloadHasher->hash($payload),
            'status' => 'pending',
        ]);

        if ($identifier['too_long']) {
            $rawProduct->errors()->create([
                'import_batch_id' => $batch->id,
                'severity' => 'warning',
                'code' => 'external_id_too_long',
                'message' => 'The source identifier exceeds the 255-character storage limit and was not indexed.',
                'raw_key' => $identifier['key'],
                'raw_value' => $identifier['raw_value'],
                'context_json' => ['max_length' => 255],
            ]);
        }

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

    /**
     * Source identifiers must not be truncated because doing so can turn distinct IDs into false matches.
     * The complete value remains available in raw_payload_json for review.
     *
     * @param  array<array-key, mixed>  $payload
     * @param  list<string>  $keys
     * @return array{value: string|null, key: string|null, raw_value: string|null, too_long: bool}
     */
    private function extractIdentifier(array $payload, array $keys): array
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $payload) || ! is_scalar($payload[$key])) {
                continue;
            }

            $identifier = (string) $payload[$key];

            return [
                'value' => mb_strlen($identifier) <= 255 ? $identifier : null,
                'key' => $key,
                'raw_value' => $identifier,
                'too_long' => mb_strlen($identifier) > 255,
            ];
        }

        return [
            'value' => null,
            'key' => null,
            'raw_value' => null,
            'too_long' => false,
        ];
    }
}
