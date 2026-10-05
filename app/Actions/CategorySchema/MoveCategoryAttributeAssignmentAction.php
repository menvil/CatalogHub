<?php

namespace App\Actions\CategorySchema;

use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\User;
use App\Services\AttributeGlobalization\CategoryAssignmentWriter;

final readonly class MoveCategoryAttributeAssignmentAction
{
    public function __construct(private CategoryAssignmentWriter $writer) {}

    public function handle(CategoryAttributeAssignment $assignment, ?int $sectionId, int $position, int $expectedRevision, ?User $actor = null): CategoryAttributeAssignment
    {
        return $this->writer->move($assignment, $sectionId, $position, $expectedRevision, $actor);
    }
}
