<?php

namespace App\Actions\CentralCatalog;

use App\Data\CentralCatalog\CentralCategoryExpectedState;
use App\Data\CentralCatalog\CentralCategorySaveData;
use App\Enums\Permission;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Queries\CentralCatalog\CategorySlugImpactQuery;
use App\Services\Categories\CategoryAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Canonical CA-018 Edit save: one atomic transaction composed of existing actions.
 *
 * Order keeps the established lock discipline: an optional structural change
 * (reparent OR complete-set sibling reorder, never both) takes the root tree
 * lock first; the identity update then locks the Category row and compares the
 * expected identity/parent; a changed slug finally re-verifies the reviewed
 * routing impact. Any failure rolls back every write, revision and audit entry.
 */
final readonly class SaveCentralCategoryAction
{
    public function __construct(
        private CategoryAccess $access,
        private ReparentCentralCategoryAction $reparent,
        private ReorderCentralCategoriesAction $reorder,
        private UpdateCentralCategoryAction $update,
        private CategorySlugImpactQuery $slugImpact,
    ) {}

    public function handle(User $actor, CentralCategory $category, CentralCategorySaveData $input): CentralCategory
    {
        $this->access->authorize(Permission::CatalogCategoriesManage, $actor);
        $order = $input->requestedOrder((int) $category->id);
        if ($input->slugChanges() && $input->slugImpactFingerprint === null) {
            throw ValidationException::withMessages(['slug_impact' => 'Review the routing impact of the slug change before saving.']);
        }

        return DB::transaction(function () use ($actor, $category, $input, $order): CentralCategory {
            if ($input->changesParent()) {
                $snapshot = clone $category;
                $snapshot->parent_id = $input->expected->parentId;
                $this->reparent->handle($actor, $snapshot, $input->parentId, $input->originalScopeRevision, $input->destinationScopeRevision);
            } elseif ($order !== null) {
                $this->reorder->handle($actor, $input->expected->parentId, $order, $input->originalScopeRevision);
            }
            $saved = $this->update->handle($actor, $category, ['name' => $input->name, 'slug' => $input->slug],
                new CentralCategoryExpectedState($input->expected->name, $input->expected->slug, $input->parentId));
            if ($input->slugChanges()) {
                $this->verifyReviewedSlugImpact($saved, $input);
            }

            return $saved;
        }, 3);
    }

    private function verifyReviewedSlugImpact(CentralCategory $category, CentralCategorySaveData $input): void
    {
        $impact = $this->slugImpact->assess($category, $input->expected->slug, $input->slug);
        if (! hash_equals($impact->fingerprint, (string) $input->slugImpactFingerprint)) {
            throw ValidationException::withMessages(['slug_impact' => 'The routing impact changed after it was reviewed. Review the updated impact and confirm again.']);
        }
        if ($impact->requiresAcknowledgement() && ! $input->slugImpactAcknowledged) {
            throw ValidationException::withMessages(['slug_impact_acknowledged' => 'Confirm that you reviewed the routing impact of this slug change.']);
        }
    }
}
