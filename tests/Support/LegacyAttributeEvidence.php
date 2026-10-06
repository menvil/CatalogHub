<?php

namespace Tests\Support;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeDefinitionCrosswalk;
use App\Models\CentralCatalog\AttributeOptionCrosswalk;

/** Explicit retained evidence fixture. This never changes live canonical identity. */
trait LegacyAttributeEvidence
{
    private function recordLegacyEvidence(): void
    {
        foreach (AttributeDefinition::query()->with(['assignments', 'options'])->get() as $definition) {
            AttributeDefinitionCrosswalk::query()->firstOrCreate(['legacy_definition_id' => $definition->id], [
                'legacy_category_id' => $definition->assignments->first()?->central_category_id,
                'legacy_code' => $definition->code, 'canonical_definition_id' => $definition->id,
                'canonical_code' => $definition->code, 'identity_status' => 'identity_preserved',
                'measurement_status' => $definition->measurement_dimension_id === null ? 'unmeasured' : 'resolved_exact',
            ]);
            foreach ($definition->options as $option) {
                AttributeOptionCrosswalk::query()->firstOrCreate(['legacy_option_id' => $option->id], [
                    'legacy_definition_id' => $definition->id, 'legacy_code' => $option->code,
                    'canonical_option_id' => $option->id, 'canonical_code' => $option->code, 'status' => 'identity_preserved',
                ]);
            }
        }
    }
}
