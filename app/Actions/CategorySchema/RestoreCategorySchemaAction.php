<?php

namespace App\Actions\CategorySchema;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\CategorySchemaStatus;
use App\Enums\Permission;
use App\Exceptions\CategorySchema\CannotTransitionCategorySchemaStatusException;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Categories\CategoryAccess;
use App\Services\Categories\CategoryLock;
use App\Services\CategorySchema\SchemaRevision;
use Illuminate\Support\Facades\DB;

final readonly class RestoreCategorySchemaAction
{
    public function __construct(private CategoryAccess $access, private CategoryLock $locks, private SchemaRevision $revision, private AuditRecorder $audit) {}

    public function handle(CentralCategory $category, ?int $expectedRevision = null, ?User $actor = null): CentralCategory
    {
        $actor = $this->access->authorize(Permission::CatalogSchemaManage, $actor);
        $expectedRevision ??= $category->schema_revision;

        return DB::transaction(function () use ($category, $expectedRevision, $actor): CentralCategory {
            $locked = $this->locks->acquireSchema([$category->id])[$category->id];
            $this->revision->expect($locked, $expectedRevision);
            if ($locked->schema_status === CategorySchemaStatus::Draft) {
                return $locked;
            }
            if ($locked->schema_status !== CategorySchemaStatus::Archived) {
                throw CannotTransitionCategorySchemaStatusException::mustBeArchived();
            }
            $before = $this->revision->snapshot($locked);
            if (! $locked->forceFill([
                'schema_status' => CategorySchemaStatus::Draft,
                ...$this->revision->clearAttribution(),
            ])->saveOrFail()) {
                throw CannotTransitionCategorySchemaStatusException::persistenceFailed();
            }
            $this->audit->record(AuditAction::CatalogCategorySchemaRestored, AuditContext::Central, $actor, $locked, null, $before, $this->revision->snapshot($locked));

            return $locked;
        }, 3);
    }
}
