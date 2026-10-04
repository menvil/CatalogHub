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

final readonly class ReparentCentralCategoryAction
{
    public function __construct(private CategoryAccess $access, private CategoryHierarchy $hierarchy, private AuditRecorder $audit) {}

    public function handle(User $actor, CentralCategory $category, ?int $parentId, int $expectedOldRevision, int $expectedNewRevision): CentralCategory
    {
        $this->access->authorize(Permission::CatalogCategoriesManage, $actor);

        return DB::transaction(function () use ($actor, $category, $parentId, $expectedOldRevision, $expectedNewRevision): CentralCategory {
            $this->hierarchy->lockTree();
            $current = CentralCategory::query()->findOrFail($category->id);
            if ($current->parent_id !== $category->parent_id) {
                throw ValidationException::withMessages(['parent_id' => 'Category parent changed. Reload before saving.']);
            }
            $oldParentId = $current->parent_id;
            $scopes = $this->hierarchy->lockScopes([$oldParentId, $parentId]);
            $oldScope = $scopes[CategoryHierarchyScope::keyFor($oldParentId)];
            $newScope = $scopes[CategoryHierarchyScope::keyFor($parentId)];
            $this->hierarchy->expect($oldScope, $expectedOldRevision);
            $this->hierarchy->expect($newScope, $expectedNewRevision);
            $siblings = $this->hierarchy->lockSiblings([$oldParentId, $parentId]);
            $this->hierarchy->validateAncestry($parentId, $current->id);
            if ($parentId === $oldParentId) {
                return $current;
            }
            $old = $siblings->where('parent_id', $oldParentId);
            $new = $siblings->where('parent_id', $parentId);
            $this->hierarchy->requireContiguous($old);
            $this->hierarchy->requireContiguous($new);
            $before = ['parent_name' => $oldParentId === null ? null : CentralCategory::query()->findOrFail($oldParentId)->name, 'parent_id' => $oldParentId, 'position' => $current->position, 'hierarchy_revision' => $oldScope->revision];
            $current->update(['parent_id' => $parentId, 'position' => $new->count()]);
            $oldIds = $old->reject(fn ($row) => $row->id === $current->id)->sortBy([['position', 'asc'], ['id', 'asc']])->pluck('id')->all();
            $newIds = [...$new->sortBy([['position', 'asc'], ['id', 'asc']])->pluck('id')->all(), $current->id];
            $this->hierarchy->persistOrder($oldIds);
            $oldScope->increment('revision');
            $newScope->increment('revision');
            $this->audit->record(AuditAction::CatalogCategoryReparented, AuditContext::Central, $actor, $current, null, $before, [
                'parent_name' => $parentId === null ? null : CentralCategory::query()->findOrFail($parentId)->name, 'parent_id' => $parentId, 'position' => $current->position, 'hierarchy_revision' => $newScope->revision,
                'old_scope_key' => $oldScope->scope_key, 'old_scope_revision' => $oldScope->revision,
                'old_ordered_ids' => $oldIds, 'ordered_ids' => $newIds,
            ]);

            return $current;
        }, 3);
    }
}
