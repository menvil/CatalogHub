<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\CentralBrandStatus;
use App\Enums\CentralProductStatus;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralBrandOwnership;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\Organization;
use App\Models\Translations\BrandTranslation;
use App\Support\Normalization\OrganizationNameNormalizer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class BrandListFixture
{
    public const VERSION = 'brands-list-v2';

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

    public static function enrich(): void
    {
        $brands = CentralBrand::query()->whereIn('slug', [
            'acer', 'anker', 'apple', 'asus', 'benq', 'bose', 'canon', 'corsair',
        ])->get()->keyBy('slug');

        if ($brands->count() !== 8) {
            throw new RuntimeException('BrandListFixture enrichment requires its first eight deterministic Brands.');
        }

        $completeProfiles = ['acer', 'apple', 'corsair'];
        foreach ($completeProfiles as $slug) {
            $brands->get($slug)?->forceFill([
                'founded_year' => ['acer' => 1976, 'apple' => 1976, 'corsair' => 1994][$slug],
                'support_url' => "https://www.{$slug}.com/support",
                'primary_color' => ['acer' => '#83B81A', 'apple' => '#111111', 'corsair' => '#F9B916'][$slug],
            ])->saveOrFail();
        }

        self::assignOwner($brands->get('apple'), 181100, 'Apple Inc.');

        $categories = collect([
            [181201, 'Computers', 'fixture-computers'],
            [181202, 'Displays', 'fixture-displays'],
            [181203, 'Accessories', 'fixture-accessories'],
            [181204, 'Audio', 'fixture-audio'],
        ])->mapWithKeys(function (array $record): array {
            [$id, $name, $slug] = $record;
            $category = CentralCategory::factory()->create(['id' => $id, 'name' => $name, 'slug' => $slug]);

            return [$slug => $category];
        });

        $productPlan = [
            'acer' => ['fixture-computers', 'fixture-computers', 'fixture-displays', 'fixture-accessories', 'fixture-accessories', 'fixture-accessories', 'fixture-displays', 'fixture-computers'],
            'anker' => ['fixture-accessories', 'fixture-accessories'],
            'apple' => ['fixture-computers', 'fixture-computers', 'fixture-accessories', 'fixture-audio', 'fixture-audio', 'fixture-accessories'],
            'asus' => ['fixture-computers', 'fixture-computers', 'fixture-displays', 'fixture-accessories', 'fixture-computers'],
            'benq' => ['fixture-displays'],
            'canon' => ['fixture-accessories', 'fixture-accessories', 'fixture-displays', 'fixture-computers'],
            'corsair' => ['fixture-computers', 'fixture-accessories', 'fixture-accessories'],
        ];
        $productId = 181300;
        foreach ($productPlan as $brandSlug => $categorySlugs) {
            foreach ($categorySlugs as $index => $categorySlug) {
                $productId++;
                $product = new CentralProduct;
                $product->forceFill([
                    'id' => $productId,
                    'central_brand_id' => $brands->get($brandSlug)?->getKey(),
                    'central_category_id' => $categories->get($categorySlug)?->getKey(),
                    'name' => ucfirst($brandSlug).' fixture product '.($index + 1),
                    'model' => strtoupper($brandSlug).'-'.($index + 1),
                    'slug' => $brandSlug.'-fixture-product-'.($index + 1),
                    'status' => $index === 0 && $brandSlug === 'anker' ? CentralProductStatus::Draft : CentralProductStatus::Active,
                    'version' => 1,
                    'created_at' => CarbonImmutable::parse('2026-08-01T09:00:00Z'),
                    'updated_at' => CarbonImmutable::parse('2026-08-01T09:00:00Z'),
                ])->saveOrFail();
            }
        }

        foreach (['acer', 'anker', 'apple', 'asus', 'benq', 'bose', 'canon', 'corsair'] as $offset => $slug) {
            if (in_array($slug, ['acer', 'anker', 'apple', 'asus', 'canon', 'corsair'], true)) {
                self::assignLogo($brands->get($slug), 181000 + $offset);
            }
        }

        $activeLocales = Locale::query()->active()->orderBy('position')->orderBy('code')->get();
        foreach ($activeLocales as $locale) {
            foreach (['acer', 'bose', 'corsair'] as $slug) {
                self::translation($brands->get($slug), $locale, TranslationStatus::HumanReviewed);
            }
        }
        foreach ([
            'anker' => [0 => TranslationStatus::MachineTranslated],
            'apple' => [0 => TranslationStatus::Approved, 1 => TranslationStatus::HumanReviewed, 2 => TranslationStatus::MachineTranslated],
            'asus' => [0 => TranslationStatus::HumanReviewed, 1 => TranslationStatus::Outdated, 2 => TranslationStatus::Approved, 3 => TranslationStatus::HumanReviewed],
            'canon' => [0 => TranslationStatus::HumanReviewed, 1 => TranslationStatus::MachineTranslated],
        ] as $slug => $statuses) {
            foreach ($statuses as $localeIndex => $status) {
                $locale = $activeLocales->get($localeIndex);
                if ($locale instanceof Locale) {
                    self::translation($brands->get($slug), $locale, $status);
                }
            }
        }
    }

    private static function assignOwner(mixed $brand, int $id, string $name): void
    {
        if (! $brand instanceof CentralBrand) {
            throw new RuntimeException('BrandListFixture owner Brand is missing.');
        }

        $normalizedName = OrganizationNameNormalizer::search($name);
        $organization = new Organization;
        $organization->forceFill([
            'id' => $id,
            'name' => $name,
            'normalized_name' => $normalizedName,
            'normalized_name_prefix' => OrganizationNameNormalizer::prefixForNormalizedName($normalizedName),
            'created_at' => CarbonImmutable::parse('2026-08-01T09:00:00Z'),
            'updated_at' => CarbonImmutable::parse('2026-08-01T09:00:00Z'),
        ])->saveOrFail();
        CentralBrandOwnership::factory()->create([
            'id' => $id,
            'central_brand_id' => $brand->getKey(),
            'organization_id' => $organization->getKey(),
        ]);
    }

    private static function assignLogo(mixed $brand, int $id): void
    {
        if (! $brand instanceof CentralBrand) {
            throw new RuntimeException('BrandListFixture logo Brand is missing.');
        }

        $bytes = (string) file_get_contents(base_path('tests/Fixtures/media/brand-logo-a.png')).$brand->slug;
        $path = "media/originals/ca-011-{$brand->slug}.png";
        Storage::disk('public')->put($path, $bytes);
        $asset = new MediaAsset;
        $asset->forceFill([
            'id' => $id,
            'uuid' => sprintf('00000000-0000-4000-8000-%012d', $id),
            'type' => 'image',
            'source' => 'fixture',
            'disk' => 'public',
            'original_path' => $path,
            'original_filename' => $brand->slug.'-logo.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($bytes),
            'width' => 320,
            'height' => 160,
            'checksum' => 'sha256:'.hash('sha256', $bytes),
            'status' => 'active',
            'created_at' => CarbonImmutable::parse('2026-08-01T09:00:00Z'),
            'updated_at' => CarbonImmutable::parse('2026-08-01T09:00:00Z'),
        ])->saveOrFail();
        MediaAssignment::factory()->create([
            'id' => $id,
            'media_asset_id' => $asset->getKey(),
            'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
            'entity_id' => $brand->getKey(),
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
            'position' => 0,
            'locale' => null,
            'site_id' => null,
            'market_id' => null,
            'is_primary' => true,
            'visibility' => 'global',
        ]);
    }

    private static function translation(mixed $brand, Locale $locale, TranslationStatus $status): void
    {
        if (! $brand instanceof CentralBrand) {
            throw new RuntimeException('BrandListFixture translation Brand is missing.');
        }

        BrandTranslation::factory()->create([
            'brand_id' => $brand->getKey(),
            'locale_id' => $locale->getKey(),
            'locale' => $locale->code,
            'name' => $brand->name.' '.$locale->code,
            'status' => $status,
        ]);
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
