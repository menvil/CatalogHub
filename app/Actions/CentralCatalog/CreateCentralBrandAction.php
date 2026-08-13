<?php

namespace App\Actions\CentralCatalog;

use App\Actions\CentralCatalog\Concerns\ValidatesCentralBrandInput;
use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;

final class CreateCentralBrandAction
{
    use ValidatesCentralBrandInput;

    /** @param array<string, mixed> $data */
    public function handle(array $data): CentralBrand
    {
        $validated = $this->validatedBrandInput($data);

        return CentralBrand::query()->create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'status' => CentralBrandStatus::Draft,
            'website_url' => $validated['website_url'],
            'country_code' => $validated['country_code'],
        ]);
    }
}
