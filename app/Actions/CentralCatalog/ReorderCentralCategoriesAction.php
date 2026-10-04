<?php

namespace App\Actions\CentralCatalog;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\Permission;
use App\Models\CentralCatalog\CategoryHierarchyScope;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Categories\CategoryAccess;
use App\Services\Categories\CategoryHierarchy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ReorderCentralCategoriesAction
{
    public function __construct(private CategoryAccess $access, private CategoryHierarchy $hierarchy, private AuditRecorder $audit) {}

    /** @param array<array-key, int> $orderedIds */
    public function handle(User $actor, ?int $parentId, array $orderedIds, int $expectedRevision): CategoryHierarchyScope
    {
        $this->access->authorize(Permission::CatalogCategoriesManage, $actor);

        return DB::transaction(function () use ($actor, $parentId, $orderedIds, $expectedRevision): CategoryHierarchyScope {
            $this->hierarchy->lockTree();
            $scope = $this->hierarchy->lockScopes([$parentId])[CategoryHierarchyScope::keyFor($parentId)];
            $this->hierarchy->expect($scope, $expectedRevision);
            $siblings = $this->hierarchy->lockSiblings([$parentId]);
            $this->hierarchy->validateAncestry($parentId);
            $ids = $orderedIds;
            sort($ids, SORT_NUMERIC);
            if (! array_is_list($orderedIds) || count(array_unique($orderedIds)) !== count($orderedIds)
                || count(array_filter($orderedIds, is_int(...))) !== count($orderedIds)
                || $ids !== $siblings->pluck('id')->all()) {
                throw ValidationException::withMessages(['ordered_ids' => 'Supply the complete sibling ID set exactly once.']);
            }
            $beforeIds = $siblings->sortBy([['position', 'asc'], ['id', 'asc']])->pluck('id')->all();
            $positions = $siblings->sortBy([['position', 'asc'], ['id', 'asc']])->pluck('position')->all();
            if ($beforeIds === $orderedIds && $positions === ($ids === [] ? [] : range(0, count($ids) - 1))) {
                return $scope;
            }
            $before = ['parent_name' => $parentId === null ? null : CentralCategory::query()->findOrFail($parentId)->name, 'parent_id' => $parentId, 'ordered_ids' => $beforeIds, 'hierarchy_revision' => $scope->revision];
            $this->hierarchy->persistOrder($orderedIds);
            $scope->increment('revision');
            $this->audit->record(AuditAction::CatalogCategoryReordered, AuditContext::Central, $actor, $scope, null, $before, [
                'parent_name' => $parentId === null ? null : CentralCategory::query()->findOrFail($parentId)->name, 'parent_id' => $parentId, 'ordered_ids' => $orderedIds, 'hierarchy_revision' => $scope->revision,
            ]);

            return $scope;
        }, 3);
    }
}
