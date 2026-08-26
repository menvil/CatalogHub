<?php

namespace Tests\Unit\Services;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralProduct;
use App\Services\Translations\TranslationSourceHashService;
use Tests\TestCase;

class TranslationSourceHashServiceTest extends TestCase
{
    public function test_generates_deterministic_source_hash_for_product(): void
    {
        $product = CentralProduct::factory()->make([
            'name' => 'LG Monitor',
            'model' => '27GP850-B',
        ]);

        $service = app(TranslationSourceHashService::class);

        $this->assertSame($service->forProduct($product), $service->forProduct($product));
    }

    public function test_changes_source_hash_when_source_text_changes(): void
    {
        $product = CentralProduct::factory()->make(['name' => 'Old Name']);

        $service = app(TranslationSourceHashService::class);
        $oldHash = $service->forProduct($product);

        $product->name = 'New Name';

        $this->assertNotSame($oldHash, $service->forProduct($product));
    }

    public function test_brand_hash_uses_only_canonical_name_and_slug(): void
    {
        $brand = CentralBrand::factory()->make([
            'name' => 'Samsung Electronics',
            'slug' => 'samsung',
            'website_url' => 'https://example.test/old',
            'country_id' => 1,
            'founded_year' => 1938,
            'support_url' => 'https://example.test/support',
            'contact_email' => 'support@example.test',
            'primary_color' => '#1428A0',
        ]);
        $service = app(TranslationSourceHashService::class);
        $initial = $service->forBrand($brand);

        $brand->website_url = 'https://example.test/new';
        $brand->country_id = 2;
        $brand->founded_year = 1969;
        $brand->support_url = 'https://example.test/help';
        $brand->contact_email = 'help@example.test';
        $brand->primary_color = '#FF0000';
        $this->assertSame($initial, $service->forBrand($brand));

        $brand->name = 'Samsung';
        $nameHash = $service->forBrand($brand);
        $this->assertNotSame($initial, $nameHash);

        $brand->slug = 'samsung-global';
        $this->assertNotSame($nameHash, $service->forBrand($brand));
    }
}
