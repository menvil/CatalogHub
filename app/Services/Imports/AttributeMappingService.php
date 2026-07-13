<?php

namespace App\Services\Imports;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\Imports\AttributeMapping;
use App\Models\Imports\RawProduct;
use Illuminate\Support\Str;

final class AttributeMappingService
{
    /** @var array<int, array<string, array<int, true>>> */
    private array $payloadProductIdsBySourceKey = [];

    /** @var array<string, int> */
    private array $usageCounts = [];

    public function resolve(int $sourceId, int $categoryId, string $rawKey): ?AttributeDefinition
    {
        $normalizedRawKey = $this->normalizeRawKey($rawKey);

        $query = AttributeMapping::query()
            ->where('import_source_id', $sourceId)
            ->where('category_id', $categoryId)
            ->where('status', 'reviewed')
            ->whereNotNull('attribute_definition_id');

        $mapping = (clone $query)->where('raw_key', trim($rawKey))->first();

        if ($mapping === null) {
            $normalizedMatches = (clone $query)
                ->where('normalized_raw_key', $normalizedRawKey)
                ->limit(2)
                ->get();

            $mapping = $normalizedMatches->count() === 1 ? $normalizedMatches->first() : null;
        }

        return $mapping?->attributeDefinition;
    }

    public function normalizeRawKey(string $rawKey): string
    {
        $normalized = Str::lower(trim($rawKey));
        $normalized = preg_replace('/[^\pL\pN]+/u', '_', $normalized) ?? '';

        return trim($normalized, '_');
    }

    public function recordUnmapped(int $sourceId, int $categoryId, string $rawKey): AttributeMapping
    {
        $rawKey = trim($rawKey);

        return AttributeMapping::query()->firstOrCreate(
            [
                'import_source_id' => $sourceId,
                'category_id' => $categoryId,
                'raw_key' => $rawKey,
            ],
            [
                'normalized_raw_key' => $this->normalizeRawKey($rawKey),
                'attribute_definition_id' => null,
                'confidence' => 0,
                'status' => 'auto',
                'mapping_type' => 'attribute',
            ]
        );
    }

    public function usageCount(AttributeMapping $mapping): int
    {
        $sourceId = (int) $mapping->import_source_id;
        $cacheKey = $sourceId."\0".$mapping->normalized_raw_key;

        if (array_key_exists($cacheKey, $this->usageCounts)) {
            return $this->usageCounts[$cacheKey];
        }

        $matchingProductIds = [];

        foreach ($this->payloadProductIdsByKeyForSource($sourceId) as $rawKey => $productIds) {
            if ($this->normalizeRawKey($rawKey) === $mapping->normalized_raw_key) {
                $matchingProductIds += $productIds;
            }
        }

        return $this->usageCounts[$cacheKey] = count($matchingProductIds);
    }

    /** @return array<string, array<int, true>> */
    private function payloadProductIdsByKeyForSource(int $sourceId): array
    {
        if (array_key_exists($sourceId, $this->payloadProductIdsBySourceKey)) {
            return $this->payloadProductIdsBySourceKey[$sourceId];
        }

        $connection = RawProduct::query()->getModel()->getConnection();
        $sql = match ($connection->getDriverName()) {
            'pgsql' => 'select distinct raw_products.id as raw_product_id, payload_key from raw_products cross join lateral jsonb_object_keys(raw_payload_json::jsonb) as payload_keys(payload_key) where import_source_id = ?',
            'mysql', 'mariadb' => "select distinct raw_products.id as raw_product_id, payload_keys.payload_key from raw_products join json_table(json_keys(raw_payload_json), '$[*]' columns (payload_key varchar(1024) path '$')) as payload_keys where import_source_id = ?",
            'sqlite' => 'select distinct raw_products.id as raw_product_id, payload_keys.key as payload_key from raw_products cross join json_each(raw_products.raw_payload_json) as payload_keys where raw_products.import_source_id = ?',
            'sqlsrv' => 'select distinct raw_products.id as raw_product_id, payload.[key] as payload_key from raw_products cross apply openjson(raw_payload_json) as payload where import_source_id = ?',
            default => null,
        };

        if ($sql === null) {
            return $this->payloadProductIdsBySourceKey[$sourceId] = [];
        }

        $productIdsByKey = [];

        foreach ($connection->select($sql, [$sourceId]) as $row) {
            $rawKey = $row->payload_key ?? null;
            $rawProductId = $row->raw_product_id ?? null;

            if (is_string($rawKey) && is_numeric($rawProductId)) {
                $productIdsByKey[$rawKey][(int) $rawProductId] = true;
            }
        }

        return $this->payloadProductIdsBySourceKey[$sourceId] = $productIdsByKey;
    }
}
