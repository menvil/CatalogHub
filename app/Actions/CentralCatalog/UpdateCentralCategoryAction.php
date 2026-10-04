<?php

namespace App\Actions\CentralCatalog;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\Permission;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Categories\CategoryAccess;
use App\Services\Categories\CategoryLock;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class UpdateCentralCategoryAction
{
    public function __construct(private CategoryAccess $access, private CategoryLock $locks, private AuditRecorder $audit) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, CentralCategory $category, array $data): CentralCategory
    {
        $this->access->authorize(Permission::CatalogCategoriesManage, $actor);
        $validated = null;

        try {
            return DB::transaction(function () use ($actor, $category, $data, &$validated): CentralCategory {
                $locked = $this->locks->acquire([$category->id])[$category->id];
                $validated = Validator::make($data, [
                    'name' => ['required', 'string', 'max:255'],
                    'slug' => ['required', 'string', 'max:255', Rule::unique('central_categories')->ignore($locked->id)],
                    'parent_id' => ['prohibited'], 'position' => ['prohibited'],
                    'status' => ['prohibited'], 'schema_status' => ['prohibited'],
                ])->validate();
                $before = ['name' => $locked->name, 'slug' => $locked->slug];
                $locked->fill($validated);
                $changed = array_keys($locked->getDirty());
                if ($changed === []) {
                    return $locked;
                }
                $locked->saveOrFail();
                $this->audit->record(AuditAction::CatalogCategoryUpdated, AuditContext::Central, $actor, $locked, null,
                    array_intersect_key($before, array_flip($changed)),
                    [...array_intersect_key($validated, array_flip($changed)), 'changed_fields' => $changed]);

                return $locked;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            if ($validated !== null && CentralCategory::query()->where('slug', $validated['slug'])->whereKeyNot($category->id)->exists()) {
                throw ValidationException::withMessages(['slug' => 'The slug has already been taken.']);
            }
            throw $exception;
        }
    }
}
