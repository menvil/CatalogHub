<?php

namespace App\Actions\CentralCatalog;

use App\Enums\Permission;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Services\Categories\CategoryAccess;
use Illuminate\Support\Facades\DB;

final readonly class SaveLegacyCentralCategoryAction
{
    public function __construct(private CategoryAccess $access, private ReparentCentralCategoryAction $reparent, private UpdateCentralCategoryAction $update) {}

    /** @param array<string, mixed> $identity */
    public function handle(User $actor, CentralCategory $category, array $identity, ?int $originalParentId, ?int $newParentId, int $oldRevision, int $newRevision): CentralCategory
    {
        $this->access->authorize(Permission::CatalogCategoriesManage, $actor);

        return DB::transaction(function () use ($actor, $category, $identity, $originalParentId, $newParentId, $oldRevision, $newRevision): CentralCategory {
            if ($newParentId !== $originalParentId) {
                $category->parent_id = $originalParentId;
                $category = $this->reparent->handle($actor, $category, $newParentId, $oldRevision, $newRevision);
            }

            return $this->update->handle($actor, $category, $identity);
        }, 3);
    }
}
