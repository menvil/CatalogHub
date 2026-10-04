<?php

namespace App\Services\CategorySchema;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\CategorySchemaStatus;
use App\Enums\Permission;
use App\Enums\SchemaMutationOrigin;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Queries\Categories\CategorySchemaFingerprintQuery;
use App\Services\Audit\AuditRecorder;
use App\Services\Categories\CategoryAccess;
use App\Services\Categories\CategoryLock;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class SchemaRevision
{
    public function __construct(private CategoryAccess $access, private CategoryLock $locks, private AuditRecorder $audit, private CategorySchemaFingerprintQuery $fingerprints) {}

    /** @template T
     * @param  Closure(): T  $mutation
     * @param  list<int>  $readCategoryIds
     * @return T
     */
    public function mutate(int $categoryId, SchemaMutationOrigin $origin, ?int $originId, Closure $mutation, ?User $actor = null, array $readCategoryIds = []): mixed
    {
        $actor = $this->access->authorize(Permission::CatalogSchemaManage, $actor);

        return DB::transaction(function () use ($categoryId, $origin, $originId, $mutation, $actor, $readCategoryIds): mixed {
            $category = $this->locks->acquire([$categoryId, ...$readCategoryIds])[$categoryId];
            if ($category->schema_status === CategorySchemaStatus::Archived) {
                throw ValidationException::withMessages(['schema_status' => 'Restore the archived schema before changing it.']);
            }
            $fingerprint = $this->fingerprints->forCategory($categoryId);
            $result = $mutation();
            if ($fingerprint !== $this->fingerprints->forCategory($categoryId)) {
                $before = $this->snapshot($category);
                $category->forceFill([
                    'schema_revision' => $category->schema_revision + 1,
                    'schema_status' => CategorySchemaStatus::Draft,
                    ...$this->clearAttribution(),
                ])->saveOrFail();
                $this->audit->record(AuditAction::CatalogCategorySchemaInvalidated, AuditContext::Central, $actor, $category, null, $before, [
                    ...$this->snapshot($category), 'reason' => $origin->value,
                    'origin_type' => explode('.', $origin->value)[0],
                    'origin_id' => $result instanceof Model ? $result->getKey() : $originId,
                ]);
            }

            return $result;
        }, 3);
    }

    public function expect(CentralCategory $category, int $expectedRevision): void
    {
        if ($category->schema_revision !== $expectedRevision) {
            throw ValidationException::withMessages(['schema_revision' => 'Schema changed. Reload before reviewing or approving.']);
        }
    }

    /** @return array<string, mixed> */
    public function snapshot(CentralCategory $category): array
    {
        return [
            'category_id' => $category->id, 'name' => $category->name, 'slug' => $category->slug,
            'schema_status' => $category->schema_status->value, 'schema_revision' => $category->schema_revision,
            'schema_reviewed_revision' => $category->schema_reviewed_revision,
            'schema_approved_revision' => $category->schema_approved_revision,
        ];
    }

    /** @return array<string, null> */
    public function clearAttribution(): array
    {
        return array_fill_keys([
            'schema_reviewed_revision', 'schema_approved_revision',
            'schema_reviewed_by_user_id', 'schema_reviewed_at',
            'schema_approved_by_user_id', 'schema_approved_at',
        ], null);
    }
}
