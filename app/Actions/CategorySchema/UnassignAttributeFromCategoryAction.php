<?php

namespace App\Actions\CategorySchema;

use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\User;
use App\Services\AttributeGlobalization\CategoryAssignmentWriter;

final readonly class UnassignAttributeFromCategoryAction
{
    public function __construct(private CategoryAssignmentWriter $writer) {}

    public function handle(CategoryAttributeAssignment $assignment, int $expectedRevision, ?User $actor = null): void
    {
        $this->writer->unassign($assignment, $expectedRevision, $actor);
    }
}
