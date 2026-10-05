<?php

namespace App\Actions\CategorySchema;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Services\AttributeGlobalization\CategoryAssignmentWriter;

final readonly class AssignAttributeToCategoryAction
{
    public function __construct(private CategoryAssignmentWriter $writer) {}

    /** @param array<string, mixed> $data */
    public function handle(CentralCategory $category, AttributeDefinition $definition, array $data, int $expectedRevision, ?User $actor = null): CategoryAttributeAssignment
    {
        return $this->writer->assign($category, $definition, $data, $expectedRevision, $actor);
    }
}
