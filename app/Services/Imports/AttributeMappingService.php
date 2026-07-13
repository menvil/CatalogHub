<?php

namespace App\Services\Imports;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\Imports\AttributeMapping;
use App\Models\Imports\RawProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AttributeMappingService
{
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
        $matchingKeys = $this->payloadKeysForSource((int) $mapping->import_source_id)
            ->filter(fn (string $key): bool => $this->normalizeRawKey($key) === $mapping->normalized_raw_key)
            ->values();

        if ($matchingKeys->isEmpty()) {
            return 0;
        }

        return RawProduct::query()
            ->where('import_source_id', $mapping->import_source_id)
            ->where(function (Builder $query) use ($matchingKeys): void {
                foreach ($matchingKeys as $key) {
                    $query->orWhereJsonContainsKey("raw_payload_json->{$key}");
                }
            })
            ->count();
    }

    /** @return Collection<int, string> */
    private function payloadKeysForSource(int $sourceId): Collection
    {
        $connection = RawProduct::query()->getModel()->getConnection();

        return match ($connection->getDriverName()) {
            'pgsql' => collect($connection->select(
                'select distinct payload_key from raw_products cross join lateral jsonb_object_keys(raw_payload_json::jsonb) as payload_keys(payload_key) where import_source_id = ?',
                [$sourceId],
            ))->pluck('payload_key')->filter(static fn (mixed $key): bool => is_string($key))->values(),
            'mysql', 'mariadb' => collect($connection->select(
                "select distinct payload_key from raw_products join json_table(json_keys(raw_payload_json), '$[*]' columns (payload_key varchar(1024) path '$')) as payload_keys where import_source_id = ?",
                [$sourceId],
            ))->pluck('payload_key')->filter(static fn (mixed $key): bool => is_string($key))->values(),
            'sqlite' => DB::connection($connection->getName())
                ->table('raw_products')
                ->crossJoin(DB::raw('json_each(raw_products.raw_payload_json)'))
                ->where('raw_products.import_source_id', $sourceId)
                ->distinct()
                ->pluck('json_each.key')
                ->filter(static fn (mixed $key): bool => is_string($key))
                ->values(),
            'sqlsrv' => collect($connection->select(
                'select distinct payload.[key] as payload_key from raw_products cross apply openjson(raw_payload_json) as payload where import_source_id = ?',
                [$sourceId],
            ))->pluck('payload_key')->filter(static fn (mixed $key): bool => is_string($key))->values(),
            default => collect(),
        };
    }
}
