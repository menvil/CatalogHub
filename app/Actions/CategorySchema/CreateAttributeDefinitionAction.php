<?php

namespace App\Actions\CategorySchema;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\User;
use App\Services\AttributeGlobalization\AttributeIdentityLock;
use App\Services\AttributeGlobalization\CategoryAssignmentWriter;
use App\Services\AttributeGlobalization\GlobalAttributeWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Existing temporary entry point, now one global create plus local assignment. */
final class CreateAttributeDefinitionAction
{
    /** @param array<string, mixed> $data */
    public function handle(AttributeSection $section, array $data, ?User $actor = null): AttributeDefinition
    {
        return DB::transaction(function () use ($section, $data, $actor): AttributeDefinition {
            app(AttributeIdentityLock::class)->acquire();
            $category = $section->category()->lockForUpdate()->firstOrFail();
            $canonical = array_intersect_key($data, array_flip(['code', 'name', 'data_type', 'measurement_dimension_id', 'canonical_measurement_unit_id']));
            $local = array_intersect_key($data, array_flip(['position', 'is_required', 'is_visible', 'is_searchable', 'is_sortable']));
            if (array_diff(array_keys($data), [...array_keys($canonical), ...array_keys($local)]) !== []) {
                throw ValidationException::withMessages(['attribute' => 'Only global meaning and assignment-owned configuration are accepted.']);
            }
            $definition = app(GlobalAttributeWriter::class)->create($canonical, $actor);
            app(CategoryAssignmentWriter::class)->assign($category, $definition, ['attribute_section_id' => $section->id, ...$local], $category->schema_revision, $actor);

            return $definition;
        }, 3);
    }
}
