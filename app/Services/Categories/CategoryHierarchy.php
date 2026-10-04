<?php

namespace App\Services\Categories;

use App\Models\CentralCatalog\CategoryHierarchyScope;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class CategoryHierarchy
{
    public function revision(?int $parentId): int
    {
        return (int) CategoryHierarchyScope::query()->whereKey(CategoryHierarchyScope::keyFor($parentId))->value('revision');
    }

    public function lockTree(): void
    {
        // First statement in the transaction is a write: SQLite acquires its
        // writer lock before any snapshot reads. The preseeded root always exists.
        CategoryHierarchyScope::query()->whereKey('root')->increment('revision', 0);
        CategoryHierarchyScope::query()->whereKey('root')->lockForUpdate()->firstOrFail();
    }

    /** @param list<int|null> $parents
     * @return array<string, CategoryHierarchyScope>
     */
    public function lockScopes(array $parents): array
    {
        $keys = array_values(array_unique(array_map(CategoryHierarchyScope::keyFor(...), $parents)));
        sort($keys, SORT_STRING);
        $scopes = [];
        foreach ($keys as $key) {
            // Tree lock serializes creation, avoiding nullable unique keys,
            // duplicate insert exceptions and failed PostgreSQL transactions.
            CategoryHierarchyScope::query()->firstOrCreate(['scope_key' => $key], ['revision' => 0]);
            $scopes[$key] = CategoryHierarchyScope::query()->whereKey($key)->lockForUpdate()->firstOrFail();
        }

        return $scopes;
    }

    public function expect(CategoryHierarchyScope $scope, int $expected): void
    {
        if ($expected !== $scope->revision) {
            throw ValidationException::withMessages(['hierarchy_revision' => 'The sibling scope changed. Reload before saving.']);
        }
    }

    /** @param list<int|null> $parents
     * @return Collection<int, CentralCategory>
     */
    public function lockSiblings(array $parents): Collection
    {
        // Root tree lock keeps membership stable while IDs are read.
        // Lock each PK separately: a MariaDB filesort must not choose a
        // different acquisition order from PostgreSQL or another action.
        $ids = CentralCategory::query()->where(function ($query) use ($parents): void {
            if (in_array(null, $parents, true)) {
                $query->whereNull('parent_id');
            }
            $query->orWhereIn('parent_id', array_values(array_filter($parents, fn ($id) => $id !== null)));
        })->orderBy('id')->pluck('id');
        $siblings = new Collection;
        foreach ($ids as $id) {
            $siblings->push(CentralCategory::query()->whereKey($id)->lockForUpdate()->firstOrFail());
        }

        return $siblings;
    }

    public function validateAncestry(?int $parentId, ?int $movingId = null): void
    {
        $seen = $movingId === null ? [] : [$movingId => true];
        while ($parentId !== null) {
            if (isset($seen[$parentId])) {
                throw ValidationException::withMessages(['parent_id' => 'Category ancestry must be acyclic.']);
            }
            $seen[$parentId] = true;
            $parent = CentralCategory::query()->find($parentId);
            if ($parent === null) {
                throw ValidationException::withMessages(['parent_id' => 'Parent category does not exist.']);
            }
            $parentId = $parent->parent_id;
        }
    }

    /** @param Collection<int, CentralCategory> $siblings */
    public function requireContiguous(Collection $siblings): void
    {
        $positions = $siblings->sortBy([['position', 'asc'], ['id', 'asc']])->pluck('position')->all();
        if ($positions !== ($siblings->isEmpty() ? [] : range(0, $siblings->count() - 1))) {
            throw ValidationException::withMessages(['position' => 'Legacy sibling ordering needs an explicit complete-set reorder.']);
        }
    }

    /** @param list<int> $ids */
    public function persistOrder(array $ids): void
    {
        foreach ($ids as $position => $id) {
            CentralCategory::query()->whereKey($id)->where('position', '!=', $position)->update(['position' => $position]);
        }
    }
}
