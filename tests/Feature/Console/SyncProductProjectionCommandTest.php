<?php

namespace Tests\Feature\Console;

use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncProductProjectionCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_one_product_for_a_site_code_and_locale(): void
    {
        $site = Site::factory()->create(['code' => 'de-monitors', 'default_locale' => 'de-DE']);
        $product = CentralProduct::factory()->create(['status' => CentralProductStatus::Active]);

        $this->artisan('cataloghub:sync-product', [
            'site' => 'de-monitors',
            'product' => $product->id,
            '--locale' => 'en',
        ])
            ->expectsOutputToContain('Product projection synced')
            ->assertSuccessful();

        $this->assertDatabaseHas('site_product_projections', [
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'locale' => 'en',
        ]);
    }

    public function test_it_uses_the_site_default_locale_when_locale_option_is_missing(): void
    {
        $site = Site::factory()->create(['default_locale' => 'de-DE']);
        $product = CentralProduct::factory()->create(['status' => CentralProductStatus::Active]);

        $this->artisan('cataloghub:sync-product', [
            'site' => $site->id,
            'product' => $product->id,
        ])->assertSuccessful();

        $this->assertDatabaseHas('site_product_projections', [
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'locale' => 'de-DE',
        ]);
    }

    public function test_it_returns_failure_for_an_unknown_site(): void
    {
        $product = CentralProduct::factory()->create();

        $this->artisan('cataloghub:sync-product', [
            'site' => 'missing-site',
            'product' => $product->id,
        ])
            ->expectsOutputToContain('Site not found')
            ->assertFailed();
    }
}
