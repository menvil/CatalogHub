<?php

namespace App\Actions\CategorySchema;

use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\User;
use App\Services\AttributeGlobalization\CategoryAssignmentWriter;

final readonly class UpdateCategoryAttributeAssignmentAction
{
    public function __construct(private CategoryAssignmentWriter $writer) {}

    /** @param array<string, mixed> $data */
    public function handle(CategoryAttributeAssignment $assignment, array $data, int $expectedRevision, ?User $actor = null): CategoryAttributeAssignment
    {
        return $this->writer->configure($assignment, $data, $expectedRevision, $actor);
    }
}
