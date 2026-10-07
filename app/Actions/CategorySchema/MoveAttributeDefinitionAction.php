<?php

namespace App\Actions\CategorySchema;

use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\User;
use App\Services\AttributeGlobalization\CategoryAssignmentWriter;

/** Historical action name; movement is explicitly of a Category assignment. */
final class MoveAttributeDefinitionAction
{
    public function handle(CategoryAttributeAssignment $assignment, AttributeSection $targetSection, int $position, ?User $actor = null): CategoryAttributeAssignment
    {
        return app(CategoryAssignmentWriter::class)->move($assignment, $targetSection->id, $position, $assignment->category->schema_revision, $actor);
    }
}
