<?php

namespace App\Services\AttributeGlobalization;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\Permission;
use App\Enums\SchemaMutationOrigin;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeDefinitionCrosswalk;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Categories\CategoryAccess;
use App\Services\Categories\CategoryLock;
use App\Services\CategorySchema\SchemaRevision;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class GlobalAttributeWriter
{
    public function __construct(private CategoryAccess $access, private AttributeIdentityLock $identityLock, private CategoryLock $categories, private GlobalAttributeValidation $validation, private AttributeDependencies $dependencies, private SchemaRevision $revisions, private AuditRecorder $audit) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, ?User $actor = null): AttributeDefinition
    {
        $actor = $this->access->authorize(Permission::CatalogSchemaManage, $actor);

        return DB::transaction(function () use ($data, $actor): AttributeDefinition {
            $this->identityLock->acquire();
            $validated = $this->validation->validate($data);
            if ($validated['data_type'] === 'json') {
                throw ValidationException::withMessages(['data_type' => 'New JSON authoring requires an explicit structured contract.']);
            }
            $this->uniqueCode($validated['code']);
            $definition = AttributeDefinition::query()->create([...$validated, 'canonical_code' => $validated['code']]);
            $this->audit->record(AuditAction::CatalogAttributeCreated, AuditContext::Central, $actor, $definition, null, null, $this->snapshot($definition, 0));
            $this->identityLock->recordTargetWrite();

            return $definition;
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function update(AttributeDefinition $definition, array $data, ?User $actor = null): AttributeDefinition
    {
        $actor = $this->access->authorize(Permission::CatalogSchemaManage, $actor);

        return DB::transaction(function () use ($definition, $data, $actor): AttributeDefinition {
            $this->identityLock->acquire();
            $ids = CategoryAttributeAssignment::query()->where('attribute_definition_id', $definition->id)->orderBy('central_category_id')->pluck('central_category_id')->all();
            $categories = $this->categories->acquire($ids);
            $locked = AttributeDefinition::query()->whereKey($definition->id)->lockForUpdate()->firstOrFail();
            $input = array_intersect_key($locked->getAttributes(), array_flip(['code', 'name', 'data_type', 'measurement_dimension_id', 'canonical_measurement_unit_id']));
            $validated = $this->validation->validate([...$input, ...$data]);
            // Missing legacy relational mappings cannot be silently cleared by ordinary edits.
            if (($locked->dimension !== null || $locked->canonical_unit !== null) && $locked->measurement_dimension_id === null
                && ! array_key_exists('measurement_dimension_id', $data) && ! array_key_exists('canonical_measurement_unit_id', $data)) {
                $validated['dimension'] = $locked->dimension;
                $validated['canonical_unit'] = $locked->canonical_unit;
            }
            $before = $this->snapshot($locked, count($categories));
            $locked->fill($validated);
            $changed = array_keys($locked->getDirty());
            if ($changed === []) {
                return $locked;
            }
            foreach ($categories as $category) {
                $this->revisions->assertMutable($category);
            }
            $dangerous = array_intersect($changed, ['code', 'data_type', 'measurement_dimension_id', 'canonical_measurement_unit_id', 'dimension', 'canonical_unit']);
            if (in_array('data_type', $changed, true) && $locked->data_type->value === 'json') {
                throw ValidationException::withMessages(['data_type' => 'New JSON authoring requires an explicit structured contract.']);
            }
            if ($dangerous !== [] && $this->dependencies->forDefinition(AttributeDefinition::query()->findOrFail($locked->id)) !== []) {
                throw ValidationException::withMessages(['attribute' => 'Explicit migration required: dependent data prevents canonical identity/type/measurement changes.']);
            }
            if (in_array('code', $changed, true)) {
                $this->uniqueCode($locked->code, $locked->id);
                $locked->canonical_code = $locked->code;
            }
            $locked->saveOrFail();
            if (in_array('code', $changed, true)) {
                AttributeDefinitionCrosswalk::query()->where('canonical_definition_id', $locked->id)->update(['canonical_code' => $locked->code]);
            }
            foreach ($categories as $category) {
                $this->revisions->invalidate($category, SchemaMutationOrigin::GlobalAttributeUpdated, $locked->id, $actor);
            }
            $this->audit->record(AuditAction::CatalogAttributeUpdated, AuditContext::Central, $actor, $locked, null, [...$before, 'changed_fields' => $changed], [...$this->snapshot($locked, count($categories)), 'changed_fields' => $changed]);
            $this->identityLock->recordTargetWrite();

            return $locked;
        }, 3);
    }

    public function uniqueCode(string $code, ?int $ignoreId = null): void
    {
        $query = AttributeDefinition::query()->where(fn ($q) => $q->where('code', $code)->orWhere('canonical_code', $code));
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }
        if ($query->exists() || AttributeDefinitionCrosswalk::query()->toBase()->where('legacy_code', $code)->when($ignoreId !== null, fn ($q) => $q->where('legacy_definition_id', '!=', $ignoreId))->exists()) {
            throw ValidationException::withMessages(['code' => 'Code is already reserved by a canonical or unresolved legacy meaning.']);
        }
    }

    /** @return array<string, mixed> */
    public function snapshot(AttributeDefinition $definition, int $count): array
    {
        return ['definition_id' => $definition->id, ...array_intersect_key($definition->getAttributes(), array_flip(['code', 'name', 'data_type', 'measurement_dimension_id', 'dimension', 'canonical_measurement_unit_id', 'canonical_unit'])), 'affected_category_count' => $count];
    }
}
