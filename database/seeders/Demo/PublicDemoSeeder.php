<?php

namespace Database\Seeders\Demo;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Domains\Projections\SiteSyncService;
use App\Enums\AttributeDataType;
use App\Enums\CentralBrandStatus;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Models\SiteProductProjection;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Database\Seeder;

class PublicDemoSeeder extends Seeder
{
    public function run(SiteSyncService $sync): void
    {
        $this->call([
            MeasurementDimensionsSeeder::class,
            MetricMeasurementUnitsSeeder::class,
            ImperialMeasurementUnitsSeeder::class,
            MultiCategorySiteSeeder::class,
            MonitorsSiteSeeder::class,
            KeyboardsSiteSeeder::class,
        ]);

        $categories = CentralCategory::query()->whereIn('slug', ['monitors', 'keyboards', 'mice'])->get()->keyBy('slug');
        $brands = $this->seedBrands();
        $attributes = $this->seedSchemas($categories->all());
        $products = $this->seedProducts($categories->all(), $brands, $attributes);
        $this->seedMedia($products['aurora-27-pro']);

        $siteProducts = [
            'tech-compare-global' => array_keys($products),
            'monitor-compare-demo' => ['aurora-27-pro', 'horizon-32-studio', 'pixelcraft-24', 'summit-ultrawide'],
            'keyboard-guru-demo' => ['keystone-tkl', 'nimbus-75', 'atlas-full-size', 'ember-mini'],
        ];

        foreach ($siteProducts as $siteCode => $productSlugs) {
            $site = Site::query()->where('code', $siteCode)->firstOrFail();
            $productIds = [];

            foreach ($productSlugs as $position => $productSlug) {
                $product = $products[$productSlug];
                $productIds[] = $product->id;
                SiteProduct::query()->updateOrCreate(
                    ['site_id' => $site->id, 'central_product_id' => $product->id],
                    [
                        'visibility' => 'visible',
                        'is_featured' => $position < 4,
                        'position' => $position,
                        'published_version' => 'demo-v1',
                        'settings_json' => ['demo' => true],
                    ],
                );
            }

            SiteProduct::query()->where('site_id', $site->id)->whereNotIn('central_product_id', $productIds)->delete();
            $sync->syncSite($site);
        }

        $multiSite = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        $multiSite->homeBlocks()->where('block_code', 'top_products')->update([
            'config_json' => [
                'title' => 'Top products',
                'limit' => 12,
                'source' => 'popular',
                'layout' => 'grid',
            ],
        ]);
        $this->enrichProjectionPayloads();
    }

    /** @return array<string, CentralBrand> */
    private function seedBrands(): array
    {
        $brands = [];

        foreach ([
            'northstar' => 'Northstar',
            'keyforge' => 'Keyforge',
            'vector-labs' => 'Vector Labs',
        ] as $slug => $name) {
            $brands[$slug] = CentralBrand::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'status' => CentralBrandStatus::Active],
            );
        }

        return $brands;
    }

    /**
     * @param  array<string, CentralCategory>  $categories
     * @return array<string, AttributeDefinition>
     */
    private function seedSchemas(array $categories): array
    {
        $schema = [
            'monitors' => [
                'section' => 'Display and power',
                'attributes' => [
                    'refresh_rate' => ['name' => 'Refresh rate', 'dimension' => 'frequency', 'unit' => 'hertz'],
                    'power_draw' => ['name' => 'Power draw', 'dimension' => 'power', 'unit' => 'watt'],
                ],
            ],
            'keyboards' => [
                'section' => 'Performance and size',
                'attributes' => [
                    'polling_rate' => ['name' => 'Polling rate', 'dimension' => 'frequency', 'unit' => 'hertz'],
                    'width' => ['name' => 'Width', 'dimension' => 'length', 'unit' => 'millimeter'],
                ],
            ],
            'mice' => [
                'section' => 'Performance and ergonomics',
                'attributes' => [
                    'polling_rate' => ['name' => 'Polling rate', 'dimension' => 'frequency', 'unit' => 'hertz'],
                    'weight' => ['name' => 'Weight', 'dimension' => 'mass', 'unit' => 'gram'],
                ],
            ],
        ];
        $attributes = [];

        foreach ($schema as $categorySlug => $definition) {
            $category = collect($categories)->first(fn (CentralCategory $candidate): bool => $candidate->slug === $categorySlug);
            if (! $category instanceof CentralCategory) {
                continue;
            }
            $section = AttributeSection::query()->updateOrCreate(
                ['central_category_id' => $category->id, 'code' => 'core_specs'],
                [
                    'name' => $definition['section'],
                    'position' => 0,
                    'display_style' => 'table',
                    'is_collapsible' => false,
                    'is_visible' => true,
                ],
            );

            $attributePosition = 0;
            foreach ($definition['attributes'] as $code => $attribute) {
                $attributes[$categorySlug.'.'.$code] = AttributeDefinition::query()->updateOrCreate(
                    ['central_category_id' => $category->id, 'code' => $code],
                    [
                        'attribute_section_id' => $section->id,
                        'name' => $attribute['name'],
                        'data_type' => AttributeDataType::Integer,
                        'dimension' => $attribute['dimension'],
                        'canonical_unit' => $attribute['unit'],
                        'position' => $attributePosition++,
                        'is_required' => true,
                        'is_filterable' => true,
                        'is_sortable' => true,
                        'is_comparable' => true,
                        'is_visible' => true,
                        'is_searchable' => true,
                    ],
                );
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, CentralCategory>  $categories
     * @param  array<string, CentralBrand>  $brands
     * @param  array<string, AttributeDefinition>  $attributes
     * @return array<string, CentralProduct>
     */
    private function seedProducts(array $categories, array $brands, array $attributes): array
    {
        $definitions = [
            'aurora-27-pro' => ['Aurora 27 Pro', 'A27P', 'monitors', 'northstar', ['refresh_rate' => 165, 'power_draw' => 42]],
            'horizon-32-studio' => ['Horizon 32 Studio', 'H32S', 'monitors', 'northstar', ['refresh_rate' => 144, 'power_draw' => 48]],
            'pixelcraft-24' => ['PixelCraft 24', 'P24', 'monitors', 'northstar', ['refresh_rate' => 180, 'power_draw' => 32]],
            'summit-ultrawide' => ['Summit UltraWide', 'S34U', 'monitors', 'northstar', ['refresh_rate' => 160, 'power_draw' => 58]],
            'keystone-tkl' => ['Keystone TKL', 'K87', 'keyboards', 'keyforge', ['polling_rate' => 1000, 'width' => 360]],
            'nimbus-75' => ['Nimbus 75', 'N75', 'keyboards', 'keyforge', ['polling_rate' => 1000, 'width' => 325]],
            'atlas-full-size' => ['Atlas Full Size', 'A104', 'keyboards', 'keyforge', ['polling_rate' => 1000, 'width' => 440]],
            'ember-mini' => ['Ember Mini', 'E60', 'keyboards', 'keyforge', ['polling_rate' => 8000, 'width' => 292]],
            'vector-pro' => ['Vector Pro', 'VP1', 'mice', 'vector-labs', ['polling_rate' => 8000, 'weight' => 59]],
            'orbit-wireless' => ['Orbit Wireless', 'OW2', 'mice', 'vector-labs', ['polling_rate' => 4000, 'weight' => 72]],
            'pulse-mini' => ['Pulse Mini', 'PM3', 'mice', 'vector-labs', ['polling_rate' => 1000, 'weight' => 52]],
            'glacier-ergo' => ['Glacier Ergo', 'GE4', 'mice', 'vector-labs', ['polling_rate' => 2000, 'weight' => 78]],
        ];
        $products = [];

        foreach ($definitions as $slug => [$name, $model, $categorySlug, $brandSlug, $specs]) {
            $category = collect($categories)->first(fn (CentralCategory $candidate): bool => $candidate->slug === $categorySlug);
            if (! $category instanceof CentralCategory) {
                continue;
            }
            $product = CentralProduct::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'central_brand_id' => $brands[$brandSlug]->id,
                    'central_category_id' => $category->id,
                    'name' => $name,
                    'model' => $model,
                    'status' => CentralProductStatus::Active,
                ],
            );
            $products[$slug] = $product;

            foreach ($specs as $attributeCode => $value) {
                $attribute = $attributes[$categorySlug.'.'.$attributeCode];
                CentralProductAttributeValue::query()->updateOrCreate(
                    ['central_product_id' => $product->id, 'attribute_definition_id' => $attribute->id],
                    [
                        'raw_value' => (string) $value,
                        'value_type' => 'integer',
                        'value_number' => $value,
                        'source_unit' => $attribute->canonical_unit,
                        'canonical_value' => $value,
                        'canonical_unit' => $attribute->canonical_unit,
                        'confidence' => 1,
                        'source_type' => 'demo_seed',
                    ],
                );
            }
        }

        return $products;
    }

    private function seedMedia(CentralProduct $product): void
    {
        $assets = [
            'main' => MediaAsset::query()->updateOrCreate(
                ['uuid' => '00000000-0000-4000-8000-000000000131'],
                [
                    'type' => 'image',
                    'source' => 'demo_seed',
                    'disk' => 'public',
                    'original_path' => 'demo/aurora-27-pro-main.svg',
                    'original_filename' => 'aurora-27-pro-main.svg',
                    'mime_type' => 'image/svg+xml',
                    'file_size' => 1024,
                    'width' => 1200,
                    'height' => 900,
                    'checksum' => 'sha256:'.hash('sha256', 'aurora-27-pro-main'),
                    'status' => 'active',
                ],
            ),
            'gallery' => MediaAsset::query()->updateOrCreate(
                ['uuid' => '00000000-0000-4000-8000-000000000132'],
                [
                    'type' => 'image',
                    'source' => 'demo_seed',
                    'disk' => 'public',
                    'original_path' => 'demo/aurora-27-pro-side.svg',
                    'original_filename' => 'aurora-27-pro-side.svg',
                    'mime_type' => 'image/svg+xml',
                    'file_size' => 1024,
                    'width' => 1200,
                    'height' => 900,
                    'checksum' => 'sha256:'.hash('sha256', 'aurora-27-pro-side'),
                    'status' => 'active',
                ],
            ),
        ];

        foreach ($assets as $role => $asset) {
            MediaAssignment::query()->updateOrCreate(
                [
                    'entity_type' => 'central_product',
                    'entity_id' => $product->id,
                    'role' => $role,
                    'locale' => null,
                    'site_id' => null,
                    'market_id' => null,
                ],
                [
                    'media_asset_id' => $asset->id,
                    'position' => 0,
                    'is_primary' => true,
                    'visibility' => 'global',
                ],
            );
        }
    }

    private function enrichProjectionPayloads(): void
    {
        SiteProductProjection::query()
            ->where('status', ProjectionStatus::Active)
            ->each(function (SiteProductProjection $projection): void {
                $payload = $projection->payload_json ?? [];
                $payload['benefits'] = [
                    'Deterministic demo data for public QA',
                    'Specifications prepared by the projection engine',
                ];
                $payload['rating'] = [
                    'value' => 4.2 + (($projection->central_product_id % 6) / 10),
                    'review_count' => 20 + ($projection->central_product_id * 3),
                ];
                $keySpecs = collect(data_get($payload, 'spec_sections', []))
                    ->flatMap(fn (mixed $section): array => is_array($section) && is_array($section['attributes'] ?? null) ? $section['attributes'] : [])
                    ->pluck('display_value')
                    ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
                    ->take(3)
                    ->values()
                    ->all();
                $summary = $projection->search_summary_json ?? [];
                $summary['key_specs'] = $keySpecs;
                $summary['rating'] = $payload['rating'];
                $checksum = hash('sha256', json_encode([
                    'status' => ProjectionStatus::Active->value,
                    'payload' => $payload,
                    'seo' => $projection->seo_json ?? [],
                    'media' => $projection->media_json ?? [],
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

                $projection->update([
                    'payload_json' => $payload,
                    'search_summary_json' => $summary,
                    'checksum' => $checksum,
                ]);
            });
    }
}
