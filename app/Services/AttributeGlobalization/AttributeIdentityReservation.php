<?php

namespace App\Services\AttributeGlobalization;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeDefinitionCrosswalk;
use App\Models\CentralCatalog\AttributeOptionCrosswalk;
use Illuminate\Validation\ValidationException;

/** Checks run inside the shared identity mutex and mutation transaction. */
final class AttributeIdentityReservation
{
    public function uniqueCode(string $code, ?int $ignoreId = null): void
    {
        $live = AttributeDefinition::query()->where('code', $code);
        if ($ignoreId !== null) {
            $live->where('id', '!=', $ignoreId);
        }
        if ($live->exists() || AttributeDefinitionCrosswalk::query()->where('legacy_code', $code)
            ->when($ignoreId !== null, fn ($query) => $query->where('legacy_definition_id', '!=', $ignoreId))->exists()) {
            $this->reservedCode();
        }
    }

    public function resolved(AttributeDefinition $definition): bool
    {
        return ! AttributeDefinitionCrosswalk::query()
            ->where('legacy_definition_id', $definition->id)->where(fn ($query) => $query->whereNull('canonical_definition_id')->orWhere('canonical_definition_id', '!=', $definition->id))->exists();
    }

    public function assertResolved(AttributeDefinition $definition, string $field = 'attribute'): void
    {
        if (! $this->resolved($definition)) {
            throw ValidationException::withMessages([$field => 'Resolve canonical identity: explicit reconciliation required before changing or reusing this meaning.']);
        }
    }

    public function optionCode(int $definitionId, string $code): void
    {
        if (AttributeOptionCrosswalk::query()->whereIn('legacy_definition_id', AttributeDefinitionCrosswalk::query()->where('canonical_definition_id', $definitionId)->select('legacy_definition_id')->union(AttributeDefinition::query()->whereKey($definitionId)->select('id')))->where('legacy_code', $code)->exists()) {
            $this->reservedCode();
        }
    }

    private function reservedCode(): never
    {
        throw ValidationException::withMessages(['code' => 'Code is already reserved by a canonical or unresolved legacy meaning.']);
    }
}
