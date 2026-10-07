<?php

namespace App\Actions\Facets;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\FacetSourceType;
use App\Enums\FacetType;
use App\Enums\SchemaMutationOrigin;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use App\Models\User;
use App\Rules\Facets\ValidFacetDefinitionRule;
use App\Services\Audit\AuditRecorder;
use App\Services\CategorySchema\SchemaRevision;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class SaveCategoryFacetAction
{
    /** @param array<string, mixed> $data */
    public function handle(CentralCategory $category, ?FacetDefinition $facet, array $data, int $expectedRevision, ?User $actor = null): FacetDefinition
    {
        return app(SchemaRevision::class)->mutate($category->id, SchemaMutationOrigin::FacetConfigured, $facet?->id, function () use ($category, $facet, $data, $expectedRevision, $actor): FacetDefinition {
            $lockedCategory = CentralCategory::query()->findOrFail($category->id);
            app(SchemaRevision::class)->expect($lockedCategory, $expectedRevision);
            $locked = $facet === null ? new FacetDefinition : FacetDefinition::query()->whereKey($facet->id)->where('category_id', $category->id)->lockForUpdate()->firstOrFail();
            $allowed = ['category_attribute_assignment_id', 'code', 'label_override', 'facet_type', 'source_type', 'is_active', 'is_visible', 'is_filterable', 'is_collapsible', 'default_collapsed', 'position', 'config_json'];
            if (array_diff(array_keys($data), $allowed) !== []) {
                throw ValidationException::withMessages(['facet' => 'Facet Category and canonical meaning are derived from its assignment.']);
            }
            $input = ['position' => 0, 'is_active' => true, 'is_visible' => true, 'is_filterable' => true, 'is_collapsible' => true, 'default_collapsed' => false, 'config_json' => [], ...($locked->exists ? $locked->only($allowed) : []), ...$data, 'category_id' => $category->id];
            foreach (['facet_type', 'source_type'] as $field) {
                if (($input[$field] ?? null) instanceof \BackedEnum) {
                    $input[$field] = $input[$field]->value;
                }
            }
            $validated = Validator::make($input, [
                'code' => ['required', 'string', 'max:255', Rule::unique('facet_definitions', 'code')->where('category_id', $category->id)->ignore($facet?->id)],
                'label_override' => ['nullable', 'string', 'max:255'],
                'facet_type' => ['required', Rule::enum(FacetType::class), new ValidFacetDefinitionRule],
                'source_type' => ['required', Rule::enum(FacetSourceType::class)],
                'category_attribute_assignment_id' => ['nullable', 'integer'], 'position' => ['required', 'integer', 'min:0'],
                'is_active' => ['required', 'boolean'], 'is_visible' => ['required', 'boolean'], 'is_filterable' => ['required', 'boolean'],
                'is_collapsible' => ['required', 'boolean'], 'default_collapsed' => ['required', 'boolean'], 'config_json' => ['nullable', 'array'],
            ])->validate();
            $assignmentId = $validated['category_attribute_assignment_id'] ?? null;
            if (($validated['source_type'] === 'attribute' && ! CategoryAttributeAssignment::query()->whereKey($assignmentId)->where('central_category_id', $category->id)->exists())
                || ($validated['source_type'] !== 'attribute' && $assignmentId !== null)) {
                throw ValidationException::withMessages(['category_attribute_assignment_id' => 'Attribute facets require an assignment of the same Category; other sources have no assignment.']);
            }
            $before = $locked->exists ? $this->snapshot($locked) : null;
            $locked->fill([...$validated, 'category_id' => $category->id]);
            if ($locked->exists && ! $locked->isDirty()) {
                return $locked;
            }
            $changed = array_keys($locked->getDirty());
            $locked->saveOrFail();
            app(AuditRecorder::class)->record($facet === null ? AuditAction::CatalogCategoryFacetCreated : AuditAction::CatalogCategoryFacetUpdated,
                AuditContext::Central, $actor ?? auth()->user(), $lockedCategory, null, $before, [...$this->snapshot($locked), 'changed_fields' => $changed]);

            return $locked;
        }, $actor);
    }

    public function remove(FacetDefinition $facet, int $expectedRevision, ?User $actor = null): void
    {
        app(SchemaRevision::class)->mutate($facet->category_id, SchemaMutationOrigin::FacetConfigured, $facet->id, function () use ($facet, $expectedRevision, $actor): void {
            $category = CentralCategory::query()->findOrFail($facet->category_id);
            app(SchemaRevision::class)->expect($category, $expectedRevision);
            $locked = FacetDefinition::query()->whereKey($facet->id)->lockForUpdate()->firstOrFail();
            $before = $this->snapshot($locked);
            $locked->delete();
            app(AuditRecorder::class)->record(AuditAction::CatalogCategoryFacetRemoved, AuditContext::Central, $actor ?? auth()->user(), $category, null, $before, null);
        }, $actor);
    }

    /** @return array<string, mixed> */
    private function snapshot(FacetDefinition $facet): array
    {
        return ['facet_id' => $facet->id, 'assignment_id' => $facet->category_attribute_assignment_id,
            ...$facet->only(['code', 'source_type', 'facet_type', 'position', 'is_active', 'is_visible', 'is_filterable', 'is_collapsible', 'default_collapsed'])];
    }
}
