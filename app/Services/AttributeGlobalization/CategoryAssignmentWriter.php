<?php

namespace App\Services\AttributeGlobalization;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\Permission;
use App\Enums\SchemaMutationOrigin;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Categories\CategoryAccess;
use App\Services\Categories\CategoryLock;
use App\Services\CategorySchema\SchemaRevision;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class CategoryAssignmentWriter
{
    public function __construct(private CategoryAccess $access, private CategoryLock $locks, private SchemaRevision $revisions, private AttributeIdentityLock $identityLock, private AttributeDependencies $dependencies, private AuditRecorder $audit) {}

    /** @param array<string, mixed> $data */
    public function assign(CentralCategory $category, AttributeDefinition $definition, array $data, int $expectedRevision, ?User $actor = null): CategoryAttributeAssignment
    {
        return $this->mutate($category->id, $expectedRevision, $actor, function (CentralCategory $locked, User $actor) use ($definition, $data): CategoryAttributeAssignment {
            $definition = AttributeDefinition::query()->whereKey($definition->id)->lockForUpdate()->firstOrFail();
            if (CategoryAttributeAssignment::query()->where('central_category_id', $locked->id)->where('attribute_definition_id', $definition->id)->exists()) {
                throw ValidationException::withMessages(['attribute_definition_id' => 'The definition is already assigned.']);
            }
            $validated = $this->validate($locked->id, $data, true);
            $assignment = CategoryAttributeAssignment::query()->create([
                'central_category_id' => $locked->id, 'attribute_definition_id' => $definition->id,
                'position' => 0, 'is_required' => false, 'is_visible' => true, 'is_searchable' => false, 'is_sortable' => false,
                ...$validated,
            ]);
            $this->finish($locked, $assignment, $actor, SchemaMutationOrigin::AttributeAssigned, AuditAction::CatalogCategoryAttributeAssigned, null);

            return $assignment;
        });
    }

    /** @param array<string, mixed> $data */
    public function configure(CategoryAttributeAssignment $assignment, array $data, int $expectedRevision, ?User $actor = null): CategoryAttributeAssignment
    {
        return $this->mutate($assignment->central_category_id, $expectedRevision, $actor, function (CentralCategory $category, User $actor) use ($assignment, $data): CategoryAttributeAssignment {
            $locked = $this->assignment($assignment, $category);
            $validated = $this->validate($category->id, $data, false);
            $before = $this->snapshot($locked);
            $locked->fill($validated);
            if (! $locked->isDirty()) {
                return $locked;
            }
            $changed = array_keys($locked->getDirty());
            $locked->saveOrFail();
            $this->finish($category, $locked, $actor, SchemaMutationOrigin::AssignmentConfigured, AuditAction::CatalogCategoryAttributeConfigured, [...$before, 'changed_fields' => $changed], $changed);

            return $locked;
        });
    }

    public function move(CategoryAttributeAssignment $assignment, ?int $sectionId, int $position, int $expectedRevision, ?User $actor = null): CategoryAttributeAssignment
    {
        return $this->mutate($assignment->central_category_id, $expectedRevision, $actor, function (CentralCategory $category, User $actor) use ($assignment, $sectionId, $position): CategoryAttributeAssignment {
            $locked = $this->assignment($assignment, $category);
            $this->validate($category->id, ['attribute_section_id' => $sectionId, 'position' => $position], true);
            if ($locked->attribute_section_id === $sectionId && $locked->position === $position) {
                return $locked;
            }
            $before = $this->snapshot($locked);
            $oldSectionId = $locked->attribute_section_id;
            $target = CategoryAttributeAssignment::query()->where('central_category_id', $category->id)->where('attribute_section_id', $sectionId)->where('id', '!=', $locked->id)->orderBy('position')->orderBy('id')->lockForUpdate()->get();
            if ($position > $target->count()) {
                throw ValidationException::withMessages(['position' => 'Position must be within the target group.']);
            }
            $target->splice($position, 0, [$locked]);
            foreach ($target as $index => $row) {
                $row->fill(['attribute_section_id' => $sectionId, 'position' => $index]);
                if ($row->isDirty()) {
                    $row->saveOrFail();
                    $this->mirror($row);
                }
            }
            if ($oldSectionId !== $sectionId) {
                foreach (CategoryAttributeAssignment::query()->where('central_category_id', $category->id)->where('attribute_section_id', $oldSectionId)->orderBy('position')->orderBy('id')->lockForUpdate()->get() as $index => $row) {
                    if ($row->position !== $index) {
                        $row->forceFill(['position' => $index])->saveOrFail();
                        $this->mirror($row);
                    }
                }
            }
            $this->finish($category, $locked, $actor, SchemaMutationOrigin::AssignmentMoved, AuditAction::CatalogCategoryAttributeMoved, $before, ['attribute_section_id', 'position']);

            return $locked;
        });
    }

    public function unassign(CategoryAttributeAssignment $assignment, int $expectedRevision, ?User $actor = null): void
    {
        $this->mutate($assignment->central_category_id, $expectedRevision, $actor, function (CentralCategory $category, User $actor) use ($assignment): void {
            $locked = $this->assignment($assignment, $category);
            $definition = $locked->definition()->lockForUpdate()->firstOrFail();
            $dependencies = $this->dependencies->forDefinition($definition, $category->id);
            if ($dependencies !== []) {
                throw ValidationException::withMessages(['assignment' => 'Explicit migration required: '.implode(', ', $dependencies).'.']);
            }
            if ($definition->central_category_id === $category->id && $definition->canonical_code === null) {
                throw ValidationException::withMessages(['assignment' => 'Resolve canonical identity before removing legacy membership.']);
            }
            $before = $this->snapshot($locked);
            $locked->delete();
            if ($definition->central_category_id === $category->id) {
                $definition->forceFill(['central_category_id' => null, 'attribute_section_id' => null])->saveOrFail();
            }
            $this->revisions->invalidate($category, SchemaMutationOrigin::AttributeUnassigned, $locked->id, $actor);
            $this->audit->record(AuditAction::CatalogCategoryAttributeUnassigned, AuditContext::Central, $actor, $category, null, $before, null);
            $this->identityLock->recordTargetWrite();
        });
    }

    /** @template T
     * @param  Closure(CentralCategory, User): T  $mutation
     * @return T
     */
    private function mutate(int $categoryId, int $revision, ?User $actor, Closure $mutation): mixed
    {
        $actor = $this->access->authorize(Permission::CatalogSchemaManage, $actor);

        return DB::transaction(function () use ($categoryId, $revision, $actor, $mutation): mixed {
            $category = $this->locks->acquireSchema([$categoryId])[$categoryId];
            $this->revisions->expect($category, $revision);
            $this->revisions->assertMutable($category);

            return $mutation($category, $actor);
        }, 3);
    }

    private function assignment(CategoryAttributeAssignment $assignment, CentralCategory $category): CategoryAttributeAssignment
    {
        return CategoryAttributeAssignment::query()->whereKey($assignment->id)->where('central_category_id', $category->id)->lockForUpdate()->firstOrFail();
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function validate(int $categoryId, array $data, bool $placement): array
    {
        $rules = ['is_required' => ['sometimes', 'boolean'], 'is_visible' => ['sometimes', 'boolean'], 'is_searchable' => ['sometimes', 'boolean'], 'is_sortable' => ['sometimes', 'boolean']];
        if ($placement) {
            $rules += ['attribute_section_id' => ['nullable', 'integer'], 'position' => ['sometimes', 'integer', 'min:0', 'max:'.AttributeDefinition::MAX_POSITION]];
        }
        if (array_diff(array_keys($data), array_keys($rules)) !== []) {
            throw ValidationException::withMessages(['assignment' => 'Only assignment-owned fields may be edited; use Move for placement.']);
        }
        $validated = Validator::make($data, $rules)->validate();
        if (isset($validated['attribute_section_id']) && ! AttributeSection::query()->whereKey($validated['attribute_section_id'])->where('central_category_id', $categoryId)->exists()) {
            throw ValidationException::withMessages(['attribute_section_id' => 'Section must belong to the assignment Category.']);
        }

        return $validated;
    }

    /** @param array<string, mixed>|null $before
     * @param  list<string>  $changed
     */
    private function finish(CentralCategory $category, CategoryAttributeAssignment $assignment, User $actor, SchemaMutationOrigin $origin, AuditAction $action, ?array $before, array $changed = []): void
    {
        $this->mirror($assignment);
        $this->revisions->invalidate($category, $origin, $assignment->id, $actor);
        $this->audit->record($action, AuditContext::Central, $actor, $category, null, $before, [...$this->snapshot($assignment), 'changed_fields' => $changed]);
        $this->identityLock->recordTargetWrite();
    }

    private function mirror(CategoryAttributeAssignment $assignment): void
    {
        app(LegacyAttributeCompatibility::class)->mirrorAssignment($assignment);
    }

    /** @return array<string, mixed> */
    private function snapshot(CategoryAttributeAssignment $assignment): array
    {
        return ['assignment_id' => $assignment->id, 'category_id' => $assignment->central_category_id, 'definition_id' => $assignment->attribute_definition_id,
            'code' => $assignment->definition->code, 'section_code' => $assignment->attribute_section_id === null ? null : AttributeSection::query()->findOrFail($assignment->attribute_section_id)->code,
            ...array_intersect_key($assignment->getAttributes(), array_flip(LegacyAttributeBackfill::LOCAL_FIELDS))];
    }
}
