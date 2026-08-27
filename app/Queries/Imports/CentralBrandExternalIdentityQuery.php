<?php

declare(strict_types=1);

namespace App\Queries\Imports;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\Imports\ImportSource;
use Illuminate\Database\Eloquent\Collection;

final class CentralBrandExternalIdentityQuery
{
    public function loadForBrand(CentralBrand $brand): CentralBrand
    {
        $identities = $brand->externalIdentities()
            ->select('central_brand_external_identities.*')
            ->join('import_sources', 'import_sources.id', '=', 'central_brand_external_identities.import_source_id')
            ->with('source:id,code,name,type,status')
            ->orderBy('import_sources.name')
            ->orderBy('import_sources.code')
            ->orderBy('central_brand_external_identities.external_id')
            ->orderBy('central_brand_external_identities.id')
            ->get();

        return $brand->setRelation('externalIdentities', $identities);
    }

    /** @return Collection<int, ImportSource> */
    public function activeSources(): Collection
    {
        return ImportSource::query()
            ->select(['id', 'code', 'name', 'type', 'status'])
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('code')
            ->get();
    }

    public function sourceForLink(int $sourceId): ImportSource
    {
        return ImportSource::query()
            ->select(['id', 'code', 'name', 'type', 'status'])
            ->findOrFail($sourceId);
    }
}
