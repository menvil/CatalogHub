<?php

namespace Database\Seeders\Demo;

use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use LogicException;

final class CentralBrandsPreviewSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Central brands preview data may only be seeded locally or in tests.');
        }

        foreach ($this->brands() as $index => $name) {
            CentralBrand::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'status' => $index % 6 === 5
                        ? CentralBrandStatus::Archived
                        : CentralBrandStatus::Active,
                ],
            );
        }
    }

    /** @return list<string> */
    private function brands(): array
    {
        return [
            'Apple',
            'Samsung',
            'Sony',
            'Canon',
            'Logitech',
            "De'Longhi",
            'Bosch',
            'Herman Miller',
            'LG',
            'Panasonic',
            'Philips',
            'Lenovo',
            'ASUS',
            'Acer',
            'Dell',
            'HP',
            'Microsoft',
            'Google',
            'Amazon',
            'Nikon',
            'Fujifilm',
            'Olympus',
            'GoPro',
            'Garmin',
            'JBL',
            'Bose',
            'Sennheiser',
            'Anker',
            'Belkin',
            'Razer',
            'Corsair',
            'MSI',
            'Gigabyte',
            'Kingston',
            'Seagate',
            'Western Digital',
            'Crucial',
            'SteelSeries',
            'HyperX',
            'BenQ',
            'ViewSonic',
            'Xiaomi',
            'Huawei',
            'OnePlus',
            'Motorola',
            'Nothing',
        ];
    }
}
