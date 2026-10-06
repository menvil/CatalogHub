<?php

namespace App\Actions\CategorySchema;

use App\Enums\Permission;
use App\Models\CentralCatalog\CategoryComparisonAttribute;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use App\Models\User;
use App\Services\AttributeGlobalization\AttributeIdentityLock;
use App\Services\Categories\CategoryAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class ExportCategorySchemaAction
{
    /** @return array<string, mixed> */
    public function handle(CentralCategory $category, ?User $actor = null): array
    {
        if (! app(CategoryAccess::class)->allows(Permission::CatalogSchemaManage, false, $actor ?? auth()->user())) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($category): array {
            app(AttributeIdentityLock::class)->acquire();
            $category->load(['attributeSections' => fn ($q) => $q->ordered(),
                'attributeAssignments' => fn ($q) => $q->ordered()->with(['definition.options' => fn ($q) => $q->ordered()])]);

            return ['schema_version' => 1, 'category' => [...$category->only(['id', 'slug', 'name', 'schema_revision']), 'schema_status' => $category->schema_status->value],
                'sections' => $category->attributeSections->map(fn ($s) => $s->only(['id', 'code', 'name', 'position', 'display_style', 'is_visible', 'is_collapsible']))->all(),
                'definitions' => $category->attributeAssignments->pluck('definition')->unique('id')->sortBy('id')->map(fn ($d) => [
                    ...$d->only(['id', 'code', 'name', 'measurement_dimension_id', 'canonical_measurement_unit_id']), 'data_type' => $d->data_type->value,
                    'options' => $d->options->map(fn ($o) => $o->only(['id', 'code', 'label', 'position', 'is_visible']))->all(),
                ])->values()->all(),
                'assignments' => $category->attributeAssignments->map(fn ($a) => $a->only(['id', 'attribute_definition_id', 'attribute_section_id', 'position', 'is_required', 'is_visible', 'is_searchable', 'is_sortable']))->all(),
                'facets' => FacetDefinition::query()->where('category_id', $category->id)->ordered()->get()->map(fn ($f) => $f->except(['created_at', 'updated_at']))->all(),
                'comparison' => CategoryComparisonAttribute::query()->where('central_category_id', $category->id)->orderBy('position')->orderBy('id')->get()->map(fn ($c) => $c->only(['id', 'category_attribute_assignment_id', 'position', 'is_visible']))->all()];
        });
    }
}
