<?php

namespace App\Actions\CentralCatalog;

use App\Actions\CentralCatalog\Concerns\ValidatesCentralBrandInput;
use App\Models\CentralCatalog\CentralBrand;

final class UpdateCentralBrandAction
{
    use ValidatesCentralBrandInput;

    /** @param array<string, mixed> $data */
    public function handle(CentralBrand $brand, array $data): CentralBrand
    {
        $brand->refresh();
        $validated = $this->validatedBrandInput($data, $brand);

        $brand->forceFill([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'website_url' => $validated['website_url'],
            'country_code' => $validated['country_code'],
        ])->saveOrFail();

        return $brand->refresh();
    }
}
