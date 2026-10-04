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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final readonly class CreateCentralCategoryAction
{
    public function __construct(private CategoryAccess $access, private CategoryHierarchy $hierarchy, private AuditRecorder $audit) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data, int $expectedRevision): CentralCategory
    {
        $this->access->authorize(Permission::CatalogCategoriesManage, $actor);

        return DB::transaction(function () use ($actor, $data, $expectedRevision): CentralCategory {
            $this->hierarchy->lockTree();
            $validated = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', Rule::unique('central_categories')],
                'parent_id' => ['nullable', 'integer', 'exists:central_categories,id'],
                'position' => ['prohibited'], 'status' => ['prohibited'], 'schema_status' => ['prohibited'],
            ])->validate();
            $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;
            $scope = $this->hierarchy->lockScopes([$parentId])[CategoryHierarchyScope::keyFor($parentId)];
            $this->hierarchy->expect($scope, $expectedRevision);
            $siblings = $this->hierarchy->lockSiblings([$parentId]);
            $this->hierarchy->validateAncestry($parentId);
            $this->hierarchy->requireContiguous($siblings);
            $category = CentralCategory::query()->create([
                'name' => $validated['name'], 'slug' => $validated['slug'],
                'parent_id' => $parentId, 'position' => $siblings->count(),
                'status' => 'draft', 'schema_status' => 'draft',
            ])->refresh();
            $this->hierarchy->lockScopes([$category->id]);
            $scope->increment('revision');
            $this->audit->record(AuditAction::CatalogCategoryCreated, AuditContext::Central, $actor, $category, null, null, [
                'category_id' => $category->id, 'name' => $category->name, 'slug' => $category->slug,
                'parent_id' => $parentId, 'parent_name' => $parentId === null ? null : CentralCategory::query()->findOrFail($parentId)->name,
                'position' => $category->position, 'status' => 'draft',
                'hierarchy_revision' => $scope->revision,
            ]);

            return $category;
        }, 3);
    }
}
