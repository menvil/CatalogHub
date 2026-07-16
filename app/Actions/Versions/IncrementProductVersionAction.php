<?php

namespace App\Actions\Versions;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\ProductVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class IncrementProductVersionAction
{
    /**
     * @param  array<string, mixed>|null  $diff
     * @param  array<string, mixed>|null  $snapshot
     * @param  array<string, mixed>|null  $metadata
     */
    public function handle(
        CentralProduct $product,
        ?User $changedBy,
        string $changeType,
        ?string $reason = null,
        ?array $diff = null,
        ?array $snapshot = null,
        ?array $metadata = null,
    ): ProductVersion {
        return DB::transaction(function () use ($changeType, $changedBy, $diff, $metadata, $product, $reason, $snapshot): ProductVersion {
            $lockedProduct = CentralProduct::query()
                ->whereKey($product->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedProduct->forceFill(['version' => $lockedProduct->version + 1])->save();

            return $lockedProduct->versions()->create([
                'version' => $lockedProduct->version,
                'changed_by_user_id' => $changedBy?->getKey(),
                'change_type' => $changeType,
                'reason' => $reason,
                'snapshot_json' => $snapshot ?? $this->canonicalSnapshot($lockedProduct),
                'diff_json' => $diff,
                'metadata_json' => $metadata,
            ]);
        });
    }

    /** @return array<string, mixed> */
    private function canonicalSnapshot(CentralProduct $product): array
    {
        return $product->only([
            'central_brand_id',
            'central_category_id',
            'name',
            'model',
            'slug',
            'status',
            'version',
        ]);
    }
}
