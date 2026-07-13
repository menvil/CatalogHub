<?php

namespace App\Services\Sites;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;

final class SiteBrandVisibilityService
{
    public function hide(Site $site, CentralBrand $brand): void
    {
        $this->save($site, array_values(array_unique([...$this->hiddenIds($site), $brand->id])));
    }

    public function allow(Site $site, CentralBrand $brand): void
    {
        $this->save($site, array_values(array_diff($this->hiddenIds($site), [$brand->id])));
    }

    public function allows(Site $site, CentralBrand $brand): bool
    {
        return ! in_array($brand->id, $this->hiddenIds($site), true);
    }

    public function allowsProduct(Site $site, CentralProduct $product): bool
    {
        return $product->brand === null || $this->allows($site, $product->brand);
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
