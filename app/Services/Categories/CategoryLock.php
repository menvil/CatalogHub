<?php

namespace App\Services\Categories;

use App\Models\CentralCatalog\CentralCategory;
use App\Services\AttributeGlobalization\AttributeIdentityLock;

final class CategoryLock
{
    /** @param list<int> $ids
     * @return array<int, CentralCategory>
     */
    public function acquireSchema(array $ids): array
    {
        app(AttributeIdentityLock::class)->acquire();

        return $this->acquire($ids);
    }

    /** @param list<int> $ids
     * @return array<int, CentralCategory>
     */
    public function acquire(array $ids): array
    {
        $ids = array_values(array_unique($ids));
        sort($ids, SORT_NUMERIC);
        $categories = [];
        foreach ($ids as $id) {
            // Portable write lock before snapshot reads, including SQLite.
            CentralCategory::query()->whereKey($id)->toBase()->increment('schema_revision', 0);
            $categories[$id] = CentralCategory::query()->whereKey($id)->lockForUpdate()->firstOrFail();
        }

        return $categories;
    }
}
