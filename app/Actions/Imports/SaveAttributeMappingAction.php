<?php

namespace App\Actions\Imports;

use App\Enums\Permission;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\Imports\AttributeMapping;
use App\Models\User;
use App\Services\AttributeGlobalization\AttributeIdentityLock;
use App\Services\Categories\CategoryAccess;
use App\Services\Imports\AttributeMappingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class SaveAttributeMappingAction
{
    /** @param array<string, mixed> $data */
    public function handle(?AttributeMapping $mapping, array $data, ?User $actor = null): AttributeMapping
    {
        app(CategoryAccess::class)->authorize(Permission::CatalogSchemaManage, $actor);

        return DB::transaction(function () use ($mapping, $data): AttributeMapping {
            app(AttributeIdentityLock::class)->acquire();
            if (array_key_exists('attribute_definition_id', $data)) {
                throw ValidationException::withMessages(['attribute_definition_id' => 'Choose Category assignment; canonical definition is derived.']);
            }
            $record = $mapping === null ? new AttributeMapping : AttributeMapping::query()->whereKey($mapping->id)->lockForUpdate()->firstOrFail();
            $input = [...$record->getAttributes(), ...$data];
            if (isset($input['raw_key'])) {
                $input['normalized_raw_key'] = app(AttributeMappingService::class)->normalizeRawKey($input['raw_key']);
            }
            $validated = Validator::make($input, ['import_source_id' => ['required', 'integer', 'exists:import_sources,id'], 'category_id' => ['required', 'integer', 'exists:central_categories,id'],
                'category_attribute_assignment_id' => ['nullable', 'integer'], 'raw_key' => ['required', 'string', 'max:255'], 'normalized_raw_key' => ['required', 'string', 'max:255'],
                'confidence' => ['required', 'numeric', 'between:0,1'], 'status' => ['required', 'in:auto,reviewed,rejected'], 'mapping_type' => ['required', 'in:attribute'], 'notes' => ['nullable', 'string']])->validate();
            $assignmentId = $validated['category_attribute_assignment_id'] ?? null;
            if (($assignmentId !== null && ! CategoryAttributeAssignment::query()->whereKey($assignmentId)->where('central_category_id', $validated['category_id'])->exists())
                || ($validated['status'] === 'reviewed' && $assignmentId === null)) {
                throw ValidationException::withMessages(['category_attribute_assignment_id' => 'Reviewed mapping requires an assignment of its Category.']);
            }
            $record->fill($validated);
            if ($record->isDirty()) {
                $record->saveOrFail();
            }

            return $record;
        }, 3);
    }
}
