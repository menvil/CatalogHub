<?php

namespace App\Actions\CategorySchema;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\CategorySchemaStatus;
use App\Enums\Permission;
use App\Exceptions\CategorySchema\CannotApproveCategorySchemaException;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Categories\CategoryAccess;
use App\Services\Categories\CategoryLock;
use App\Services\CategorySchema\CategorySchemaValidator;
use App\Services\CategorySchema\SchemaRevision;
use Illuminate\Support\Facades\DB;

final readonly class ApproveCategorySchemaAction
{
    public function __construct(private CategoryAccess $access, private CategoryLock $locks, private SchemaRevision $revision, private CategorySchemaValidator $validator, private AuditRecorder $audit) {}

    public function handle(CentralCategory $category, ?int $expectedRevision = null, ?User $actor = null): CentralCategory
    {
        $actor = $this->access->authorize(Permission::CatalogSchemaManage, $actor);
        $expectedRevision ??= $category->schema_revision;

        return DB::transaction(function () use ($category, $expectedRevision, $actor): CentralCategory {
            $locked = $this->locks->acquire([$category->id])[$category->id];
            $this->revision->expect($locked, $expectedRevision);
            if ($locked->schema_status === CategorySchemaStatus::Approved && $locked->schema_approved_revision === $locked->schema_revision) {
                return $locked;
            }
            if ($locked->schema_status !== CategorySchemaStatus::Reviewed || $locked->schema_reviewed_revision !== $locked->schema_revision) {
                throw CannotApproveCategorySchemaException::mustBeReviewed();
            }
            if ($this->validator->validate($locked)->hasErrors()) {
                throw CannotApproveCategorySchemaException::hasValidationErrors();
            }
            $before = $this->revision->snapshot($locked);
            $locked->forceFill([
                'schema_status' => CategorySchemaStatus::Approved,
                'schema_approved_revision' => $locked->schema_revision,
                'schema_approved_by_user_id' => $actor->id, 'schema_approved_at' => now(),
            ])->saveOrFail();
            $this->audit->record(AuditAction::CatalogCategorySchemaApproved, AuditContext::Central, $actor, $locked, null, $before, $this->revision->snapshot($locked));

            return $locked;
        }, 3);
    }
}
