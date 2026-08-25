<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class BrandListFixture
{
    public const VERSION = 'brands-list-v1';

    /** @return Collection<int, CentralBrand> */
    public static function create(): Collection
    {
        return collect(self::records())->map(function (array $record): CentralBrand {
            $updatedAt = CarbonImmutable::parse($record['updated_at'], 'UTC');
            $countryAlpha2 = $record['country_alpha2'];
            unset($record['country_alpha2']);

            return CentralBrand::factory()->create([
                ...$record,
                'country_id' => $countryAlpha2 === null ? null : CountryReference::id($countryAlpha2),
                'created_at' => $updatedAt->subMonths(3),
            ]);
        });
    }

    /**
     * @return list<array{
     *     name: string,
     *     slug: string,
     *     status: CentralBrandStatus,
     *     website_url: ?string,
     *     country_alpha2: ?string,
     *     updated_at: string
     * }>
     */
    private static function records(): array
    {
        return [
            ['name' => 'Acer', 'slug' => 'acer', 'status' => CentralBrandStatus::Active, 'website_url' => 'https://www.acer.com', 'country_alpha2' => 'TW', 'updated_at' => '2026-08-01T09:00:00Z'],
            ['name' => 'Anker', 'slug' => 'anker', 'status' => CentralBrandStatus::Draft, 'website_url' => 'https://www.anker.com', 'country_alpha2' => 'CN', 'updated_at' => '2026-08-02T09:00:00Z'],
            ['name' => 'Apple', 'slug' => 'apple', 'status' => CentralBrandStatus::Active, 'website_url' => 'https://www.apple.com', 'country_alpha2' => 'US', 'updated_at' => '2026-08-03T09:00:00Z'],
            ['name' => 'ASUS', 'slug' => 'asus', 'status' => CentralBrandStatus::Active, 'website_url' => 'https://www.asus.com', 'country_alpha2' => 'TW', 'updated_at' => '2026-08-04T09:00:00Z'],
            ['name' => 'BenQ', 'slug' => 'benq', 'status' => CentralBrandStatus::Draft, 'website_url' => 'https://www.benq.com', 'country_alpha2' => 'TW', 'updated_at' => '2026-08-05T09:00:00Z'],
            ['name' => 'Bose', 'slug' => 'bose', 'status' => CentralBrandStatus::Archived, 'website_url' => null, 'country_alpha2' => 'US', 'updated_at' => '2026-08-06T09:00:00Z'],
            ['name' => 'Canon', 'slug' => 'canon', 'status' => CentralBrandStatus::Active, 'website_url' => 'https://global.canon', 'country_alpha2' => 'JP', 'updated_at' => '2026-08-07T09:00:00Z'],
            ['name' => 'Corsair', 'slug' => 'corsair', 'status' => CentralBrandStatus::Active, 'website_url' => 'https://www.corsair.com', 'country_alpha2' => 'US', 'updated_at' => '2026-08-08T09:00:00Z'],
            ['name' => 'Dell', 'slug' => 'dell', 'status' => CentralBrandStatus::Active, 'website_url' => 'https://www.dell.com', 'country_alpha2' => 'US', 'updated_at' => '2026-08-09T09:00:00Z'],
            ['name' => 'Gigabyte', 'slug' => 'gigabyte', 'status' => CentralBrandStatus::Draft, 'website_url' => 'https://www.gigabyte.com', 'country_alpha2' => 'TW', 'updated_at' => '2026-08-10T09:00:00Z'],
            ['name' => 'Huawei', 'slug' => 'huawei', 'status' => CentralBrandStatus::Active, 'website_url' => 'https://www.huawei.com', 'country_alpha2' => 'CN', 'updated_at' => '2026-08-11T09:00:00Z'],
            ['name' => 'JBL', 'slug' => 'jbl', 'status' => CentralBrandStatus::Active, 'website_url' => 'https://www.jbl.com', 'country_alpha2' => 'US', 'updated_at' => '2026-08-12T09:00:00Z'],
            ['name' => 'Lenovo', 'slug' => 'lenovo', 'status' => CentralBrandStatus::Active, 'website_url' => 'https://www.lenovo.com', 'country_alpha2' => 'CN', 'updated_at' => '2026-08-13T09:00:00Z'],
            ['name' => 'LG', 'slug' => 'lg', 'status' => CentralBrandStatus::Active, 'website_url' => 'https://www.lg.com', 'country_alpha2' => 'KR', 'updated_at' => '2026-07-19T09:00:00Z'],
            ['name' => 'Logitech', 'slug' => 'logitech', 'status' => CentralBrandStatus::Draft, 'website_url' => 'https://www.logitech.com/en-us', 'country_alpha2' => 'CH', 'updated_at' => '2026-07-20T09:00:00Z'],
            ['name' => 'MSI', 'slug' => 'msi', 'status' => CentralBrandStatus::Archived, 'website_url' => 'https://www.msi.com', 'country_alpha2' => 'TW', 'updated_at' => '2026-07-21T09:00:00Z'],
            ['name' => 'Nikon', 'slug' => 'nikon', 'status' => CentralBrandStatus::Active, 'website_url' => 'https://www.nikon.com', 'country_alpha2' => 'JP', 'updated_at' => '2026-07-22T09:00:00Z'],
            ['name' => 'Philips', 'slug' => 'philips', 'status' => CentralBrandStatus::Archived, 'website_url' => 'https://www.philips.com', 'country_alpha2' => 'NL', 'updated_at' => '2026-07-24T09:00:00Z'],
            ['name' => 'Razer', 'slug' => 'razer', 'status' => CentralBrandStatus::Draft, 'website_url' => 'https://www.razer.com', 'country_alpha2' => null, 'updated_at' => '2026-07-25T09:00:00Z'],
            ['name' => 'Samsung', 'slug' => 'samsung', 'status' => CentralBrandStatus::Active, 'website_url' => 'https://www.samsung.com', 'country_alpha2' => 'KR', 'updated_at' => '2026-07-26T09:00:00Z'],
            ['name' => 'Sony', 'slug' => 'sony', 'status' => CentralBrandStatus::Archived, 'website_url' => 'https://www.sony.com', 'country_alpha2' => 'JP', 'updated_at' => '2026-07-27T09:00:00Z'],
            ['name' => 'ViewSonic', 'slug' => 'viewsonic', 'status' => CentralBrandStatus::Active, 'website_url' => null, 'country_alpha2' => null, 'updated_at' => '2026-07-28T09:00:00Z'],
            ['name' => 'Xiaomi', 'slug' => 'xiaomi', 'status' => CentralBrandStatus::Draft, 'website_url' => 'https://www.mi.com', 'country_alpha2' => 'CN', 'updated_at' => '2026-07-29T09:00:00Z'],
            ['name' => 'Zotac', 'slug' => 'zotac', 'status' => CentralBrandStatus::Draft, 'website_url' => null, 'country_alpha2' => 'HK', 'updated_at' => '2026-07-23T09:00:00Z'],
        ];
    }
}
