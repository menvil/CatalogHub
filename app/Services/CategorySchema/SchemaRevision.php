<?php

namespace App\Services\CategorySchema;

use App\Domains\Projections\ProjectionStaleDetector;
use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\CategorySchemaStatus;
use App\Enums\Permission;
use App\Enums\SchemaMutationOrigin;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Queries\Categories\CategorySchemaFingerprintQuery;
use App\Services\AttributeGlobalization\AttributeIdentityLock;
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
            app(AttributeIdentityLock::class)->acquire();
            $definitionId = match ($origin) {
                SchemaMutationOrigin::AttributeUpdated, SchemaMutationOrigin::AttributeMoved => $originId,
                SchemaMutationOrigin::OptionCreated => $originId,
                SchemaMutationOrigin::OptionUpdated, SchemaMutationOrigin::OptionDeleted => AttributeOption::query()->findOrFail($originId)->attribute_definition_id,
                default => null,
            };
            $ids = [$categoryId];
            if ($definitionId !== null) {
                $ids = array_values(array_unique([...$ids, ...CategoryAttributeAssignment::query()
                    ->where('attribute_definition_id', $definitionId)->pluck('central_category_id')->all()]));
                sort($ids, SORT_NUMERIC);
            }
            $locked = $this->locks->acquire([...$ids, ...$readCategoryIds]);
            foreach ($ids as $id) {
                $this->assertMutable($locked[$id]);
            }
            $before = [];
            foreach ($ids as $id) {
                $before[$id] = $this->fingerprints->forCategory($id);
            }
            $result = $mutation();
            foreach ($ids as $id) {
                if ($before[$id] !== $this->fingerprints->forCategory($id)) {
                    $this->invalidate($locked[$id], $origin, $result instanceof Model ? (int) $result->getKey() : $originId, $actor);
                }
            }

            return $result;
        }, 3);
    }

    public function assertMutable(CentralCategory $category): void
    {
        if ($category->schema_status === CategorySchemaStatus::Archived) {
            throw ValidationException::withMessages(['schema_status' => 'Restore the archived schema before changing it.']);
        }
    }

    public function invalidate(CentralCategory $category, SchemaMutationOrigin $origin, ?int $originId, User $actor): void
    {
        $this->assertMutable($category);
        $before = $this->snapshot($category);
        $category->forceFill([
            'schema_revision' => $category->schema_revision + 1,
            'schema_status' => CategorySchemaStatus::Draft,
            ...$this->clearAttribution(),
        ])->saveOrFail();
        app(ProjectionStaleDetector::class)->markStaleForCategory($category);
        $this->audit->record(AuditAction::CatalogCategorySchemaInvalidated, AuditContext::Central, $actor, $category, null, $before, [
            ...$this->snapshot($category), 'reason' => $origin->value,
            'origin_type' => explode('.', $origin->value)[0], 'origin_id' => $originId,
        ]);
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
