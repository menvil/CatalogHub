<?php

namespace App\Actions\CentralCatalog;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\CentralCategoryStatus;
use App\Enums\Permission;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Categories\CategoryAccess;
use App\Services\Categories\CategoryLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ArchiveCentralCategoryAction
{
    public function __construct(private CategoryAccess $access, private CategoryLock $locks, private AuditRecorder $audit) {}

    public function handle(User $actor, CentralCategory $category, ?CentralCategoryStatus $expectedStatus = null): CentralCategory
    {
        $this->access->authorize(Permission::CatalogCategoriesManage, $actor);

        return DB::transaction(function () use ($actor, $category, $expectedStatus): CentralCategory {
            $locked = $this->locks->acquire([$category->id])[$category->id];
            if ($expectedStatus !== null && $locked->status !== $expectedStatus) {
                throw ValidationException::withMessages(['status' => 'The Category changed. Reload before trying again.']);
            }
            if ($locked->status === CentralCategoryStatus::Archived) {
                return $locked;
            }
            if (! in_array($locked->status, [CentralCategoryStatus::Draft, CentralCategoryStatus::Active], true)) {
                throw ValidationException::withMessages(['status' => 'This Category lifecycle transition is not allowed.']);
            }
            $before = $locked->status->value;
            $locked->update(['status' => CentralCategoryStatus::Archived]);
            $this->audit->record(AuditAction::CatalogCategoryArchived, AuditContext::Central, $actor, $locked, null,
                ['status' => $before], ['status' => $locked->status->value]);

            return $locked;
        }, 3);
    }
}
