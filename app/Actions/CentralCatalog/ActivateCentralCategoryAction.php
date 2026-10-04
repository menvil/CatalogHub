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

final readonly class ActivateCentralCategoryAction
{
    public function __construct(private CategoryAccess $access, private CategoryLock $locks, private AuditRecorder $audit) {}

    public function handle(User $actor, CentralCategory $category): CentralCategory
    {
        $this->access->authorize(Permission::CatalogCategoriesManage, $actor);

        return DB::transaction(function () use ($actor, $category): CentralCategory {
            $locked = $this->locks->acquire([$category->id])[$category->id];
            if ($locked->status === CentralCategoryStatus::Active) {
                return $locked;
            }
            if (! in_array($locked->status, [CentralCategoryStatus::Draft], true)) {
                throw ValidationException::withMessages(['status' => 'This Category lifecycle transition is not allowed.']);
            }
            $before = $locked->status->value;
            $locked->update(['status' => CentralCategoryStatus::Active]);
            $this->audit->record(AuditAction::CatalogCategoryActivated, AuditContext::Central, $actor, $locked, null,
                ['status' => $before], ['status' => $locked->status->value]);

            return $locked;
        }, 3);
    }
}
