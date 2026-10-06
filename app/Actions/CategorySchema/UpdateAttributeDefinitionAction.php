<?php

namespace App\Actions\CategorySchema;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\User;
use App\Services\AttributeGlobalization\GlobalAttributeWriter;

/** Canonical updates cannot change Category-local assignment configuration. */
final class UpdateAttributeDefinitionAction
{
    /** @param array<string, mixed> $data */
    public function handle(AttributeDefinition $attribute, array $data, ?User $actor = null): AttributeDefinition
    {
        return app(GlobalAttributeWriter::class)->update($attribute, $data, $actor);
    }
}
