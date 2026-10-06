<?php

namespace App\Actions\CategorySchema;

use App\Enums\SchemaMutationOrigin;
use App\Exceptions\CategorySchema\CannotCloneCategorySchemaException;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Services\CategorySchema\SchemaRevision;
use Illuminate\Support\Facades\DB;

final class CloneCategorySchemaAction
{
    public function handle(CentralCategory $source, CentralCategory $target, ?User $actor = null): void
    {
        $categoryId = $target->id;
        app(SchemaRevision::class)->mutate($categoryId, SchemaMutationOrigin::SchemaCloned, $source->id, function () use ($source, $target): void {
            $this->perform($source, $target);
        }, $actor, [$source->id]);
    }

    private function perform(CentralCategory $source, CentralCategory $target): void
    {
        $source = CentralCategory::query()->findOrFail($source->id);
        $target = CentralCategory::query()->findOrFail($target->id);

        if ($source->is($target)) {
            throw CannotCloneCategorySchemaException::sourceAndTargetAreSame();
        }

        DB::transaction(function () use ($source, $target): void {
            /** @var CentralCategory $lockedTarget */
            $lockedTarget = $target->newQuery()->whereKey($target->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedTarget->attributeSections()->exists() || $lockedTarget->attributeAssignments()->exists()) {
                throw CannotCloneCategorySchemaException::targetSchemaIsNotEmpty();
            }

            $source->load([
                'attributeSections' => fn ($query) => $query->ordered(),
                'attributeAssignments' => fn ($query) => $query->ordered()->with('definition'),
            ]);

            $sectionMap = [];

            foreach ($source->attributeSections as $section) {
                $clonedSection = AttributeSection::query()->create([
                    'central_category_id' => $lockedTarget->getKey(),
                    'code' => $section->code,
                    'name' => $section->name,
                    'position' => $section->position,
                    'display_style' => $section->display_style,
                    'is_collapsible' => $section->is_collapsible,
                    'is_visible' => $section->is_visible,
                ]);

                $sectionMap[$section->getKey()] = $clonedSection;
            }

            foreach ($source->attributeAssignments as $assignment) {
                CategoryAttributeAssignment::query()->create([
                    'central_category_id' => $target->id, 'attribute_definition_id' => $assignment->attribute_definition_id,
                    'attribute_section_id' => $assignment->attribute_section_id === null ? null : $sectionMap[$assignment->attribute_section_id]->id,
                    ...$assignment->only(['position', 'is_required', 'is_visible', 'is_searchable', 'is_sortable']),
                ]);
            }

        });
    }
}
