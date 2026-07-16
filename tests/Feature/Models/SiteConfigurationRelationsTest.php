<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SiteConfigurationRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_configuration_relations_expose_enabled_records_in_position_order(): void
    {
        $site = Site::factory()->create();
        Locale::factory()->create(['code' => 'de-DE']);
        Locale::factory()->create(['code' => 'fr-FR']);
        $firstCategory = CentralCategory::factory()->create();
        $secondCategory = CentralCategory::factory()->create();

        $site->locales()->create([
            'locale_code' => 'fr-FR',
            'is_enabled' => false,
            'position' => 0,
        ]);
        $site->locales()->create([
            'locale_code' => 'de-DE',
            'is_enabled' => true,
            'position' => 1,
        ]);
        $site->categories()->create([
            'central_category_id' => $secondCategory->getKey(),
            'is_enabled' => false,
            'position' => 0,
        ]);
        $site->categories()->create([
            'central_category_id' => $firstCategory->getKey(),
            'is_enabled' => true,
            'position' => 1,
        ]);

        $this->assertSame(
            ['de-DE'],
            $site->locales()->enabled()->ordered()->pluck('locale_code')->all(),
        );
        $this->assertSame(
            [$firstCategory->getKey()],
            $site->categories()->enabled()->ordered()->pluck('central_category_id')->all(),
        );
    }

    public function test_site_products_visible_scope_returns_only_visible_configuration_rows(): void
    {
        $site = Site::factory()->create();
        $visibleProduct = CentralProduct::factory()->create();
        $hiddenProduct = CentralProduct::factory()->create();

        $site->products()->create([
            'central_product_id' => $visibleProduct->getKey(),
            'visibility' => 'visible',
        ]);
        $site->products()->create([
            'central_product_id' => $hiddenProduct->getKey(),
            'visibility' => 'hidden',
        ]);

        $this->assertSame(
            [$visibleProduct->getKey()],
            $site->products()->visible()->pluck('central_product_id')->all(),
        );
    }
}
