<?php

namespace App\Queries\Attributes;

use App\Enums\Permission;
use App\Models\CentralCatalog\AttributeIdentityScope;
use App\Models\User;
use App\Services\Categories\CategoryAccess;
use Illuminate\Auth\Access\AuthorizationException;

final class AttributeGlobalizationDiagnosticsQuery
{
    /** @return array<string, mixed> */
    public function report(?User $actor): array
    {
        if (! app(CategoryAccess::class)->allows(Permission::CatalogSchemaManage, false, $actor)) {
            throw new AuthorizationException;
        }

        $version = AttributeIdentityScope::query()->whereKey(1)->firstOrFail()->getAttribute('consumer_version');

        return $version === null ? app(HistoricalAttributeGlobalizationDiagnosticsQuery::class)->report($actor)
            : app(SchemaConsumerPreflightV2Query::class)->report();
    }
}
