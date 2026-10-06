<?php

namespace App\Actions\CategorySchema;

use App\Enums\Permission;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\User;
use App\Services\AttributeGlobalization\AttributeIdentityLock;
use App\Services\Categories\CategoryAccess;
use App\Services\Categories\CategoryLock;
use App\Services\CategorySchema\SchemaRevision;
use Illuminate\Support\Facades\DB;

/** Orchestrates the existing temporary editor's explicit global and local mutations. */
final class UpdateAssignedAttributeAction
{
    /** @param array<string, mixed> $data */
    public function handle(CategoryAttributeAssignment $assignment, array $data, int $expectedRevision, ?User $actor = null): void
    {
        $actor = app(CategoryAccess::class)->authorize(Permission::CatalogSchemaManage, $actor);
        DB::transaction(function () use ($assignment, $data, $expectedRevision, $actor): void {
            app(AttributeIdentityLock::class)->acquireTarget();
            $fields = array_flip(['attribute_section_id', 'position', 'is_required', 'is_visible', 'is_searchable', 'is_sortable']);
            $local = array_intersect_key($data, $fields);
            $canonical = array_diff_key($data, $fields);
            $categoryIds = $canonical === [] ? [$assignment->central_category_id] : CategoryAttributeAssignment::query()
                ->where('attribute_definition_id', $assignment->attribute_definition_id)->pluck('central_category_id')->all();
            $categories = app(CategoryLock::class)->acquire($categoryIds);
            app(SchemaRevision::class)->expect($categories[$assignment->central_category_id], $expectedRevision);
            if ($local !== []) {
                app(UpdateCategoryAttributeAssignmentAction::class)->handle($assignment, $local, $expectedRevision, $actor);
            }
            if ($canonical !== []) {
                app(UpdateAttributeDefinitionAction::class)->handle($assignment->definition, $canonical, $actor);
            }
        });
    }
}
