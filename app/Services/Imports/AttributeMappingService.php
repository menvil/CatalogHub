<?php

namespace App\Services\Imports;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\Imports\AttributeMapping;
use App\Models\Imports\RawProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class AttributeMappingService
{
    public function resolve(int $sourceId, int $categoryId, string $rawKey): ?AttributeDefinition
    {
        $normalizedRawKey = $this->normalizeRawKey($rawKey);

        $mapping = AttributeMapping::query()
            ->where('import_source_id', $sourceId)
            ->where('category_id', $categoryId)
            ->where('status', 'reviewed')
            ->whereNotNull('attribute_definition_id')
            ->where(function (Builder $query) use ($rawKey, $normalizedRawKey): void {
                $query->where('raw_key', trim($rawKey))
                    ->orWhere('normalized_raw_key', $normalizedRawKey);
            })
            ->orderByRaw('CASE WHEN raw_key = ? THEN 0 ELSE 1 END', [trim($rawKey)])
            ->first();

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
        return RawProduct::query()
            ->where('import_source_id', $mapping->import_source_id)
            ->get(['raw_payload_json'])
            ->filter(fn (RawProduct $rawProduct): bool => array_key_exists(
                $mapping->raw_key,
                $rawProduct->raw_payload_json,
            ))
            ->count();
    }
}
