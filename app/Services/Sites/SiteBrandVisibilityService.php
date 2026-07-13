<?php

namespace App\Services\Sites;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use Illuminate\Support\Facades\DB;

final class SiteBrandVisibilityService
{
    public function hide(Site $site, CentralBrand $brand): void
    {
        DB::transaction(function () use ($brand, $site): void {
            $lockedSite = Site::query()->whereKey($site)->lockForUpdate()->firstOrFail();
            $this->save($lockedSite, array_values(array_unique([...$this->hiddenIds($lockedSite), $brand->id])));
        });
    }

    public function allow(Site $site, CentralBrand $brand): void
    {
        DB::transaction(function () use ($brand, $site): void {
            $lockedSite = Site::query()->whereKey($site)->lockForUpdate()->firstOrFail();
            $this->save($lockedSite, array_values(array_diff($this->hiddenIds($lockedSite), [$brand->id])));
        });
    }

    public function allows(Site $site, CentralBrand $brand): bool
    {
        return ! in_array($brand->id, $this->hiddenIds($site), true);
    }

    public function allowsProduct(Site $site, CentralProduct $product): bool
    {
        $brandId = $product->getAttribute('central_brand_id');

        return $brandId === null || ! in_array((int) $brandId, $this->hiddenIds($site), true);
    }

    /** @return list<int> */
    private function hiddenIds(Site $site): array
    {
        return array_values(array_map('intval', $site->settings_json['hidden_brand_ids'] ?? []));
    }

    /** @param list<int> $ids */
    private function save(Site $site, array $ids): void
    {
        $settings = $site->settings_json ?? [];
        $settings['hidden_brand_ids'] = $ids;
        $site->update(['settings_json' => $settings]);
    }
}
