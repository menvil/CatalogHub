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
        $live = AttributeDefinition::query()->where(fn ($query) => $query->where('code', $code)->orWhere('canonical_code', $code));
        if ($ignoreId !== null) {
            $live->where('id', '!=', $ignoreId);
        }
        if ($live->exists() || AttributeDefinitionCrosswalk::query()->where('legacy_code', $code)
            ->when($ignoreId !== null, fn ($query) => $query->where('legacy_definition_id', '!=', $ignoreId))->exists()) {
            $this->reservedCode();
        }
    }

    public function legacyCreateCode(string $code): void
    {
        // Live local duplicate groups remain inventory, never an approved merge.
        // A durable owner which has moved away from this code cannot be reused.
        $historical = AttributeDefinitionCrosswalk::query()->toBase()
            ->leftJoin('attribute_definitions as owner', 'owner.id', '=', 'attribute_definition_crosswalks.legacy_definition_id')
            ->where('legacy_code', $code)
            ->where(fn ($query) => $query->whereNull('owner.id')->orWhere('owner.code', '!=', $code));
        $global = AttributeDefinition::query()->whereNull('central_category_id')
            ->where(fn ($query) => $query->where('code', $code)->orWhere('canonical_code', $code));
        if ($historical->exists() || $global->exists()) {
            $this->reservedCode();
        }
    }

    public function resolved(AttributeDefinition $definition): bool
    {
        return $definition->canonical_code !== null && ! AttributeDefinitionCrosswalk::query()
            ->where('legacy_definition_id', $definition->id)->whereNull('canonical_definition_id')->exists();
    }

    public function assertResolved(AttributeDefinition $definition, string $field = 'attribute'): void
    {
        if (! $this->resolved($definition)) {
            throw ValidationException::withMessages([$field => 'Resolve canonical identity: explicit reconciliation required before changing or reusing this meaning.']);
        }
    }

    public function optionCode(int $definitionId, string $code): void
    {
        if (AttributeOptionCrosswalk::query()->where('legacy_definition_id', $definitionId)->where('legacy_code', $code)->exists()) {
            $this->reservedCode();
        }
    }

    private function reservedCode(): never
    {
        throw ValidationException::withMessages(['code' => 'Code is already reserved by a canonical or unresolved legacy meaning.']);
    }
}
