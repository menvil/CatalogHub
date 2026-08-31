<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralBrandOwnership;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CentralBrandOwnership> */
final class CentralBrandOwnershipFactory extends Factory
{
    protected $model = CentralBrandOwnership::class;

    public function definition(): array
    {
        return [
            'central_brand_id' => CentralBrand::factory(),
            'organization_id' => Organization::factory(),
        ];
    }
}
