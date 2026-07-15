<?php

namespace Tests\Feature\Public;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\MarketOffer;
use App\Models\Site;
use App\Models\SiteProductProjection;
use App\Services\Pricing\ExternalWidgetRenderer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ExternalPriceWidgetFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_a_controlled_external_widget_when_configured_and_offers_are_missing(): void
    {
        Config::set('pricing.external_widgets.providers.trusted_test', [
            'base_url' => 'https://widgets.trusted.test/offers',
        ]);
        [$site, $projection] = $this->scenario([
            'pricing' => [
                'provider_mode' => 'auto',
                'external_widget' => [
                    'enabled' => true,
                    'provider' => 'trusted_test',
                    'publisher_id' => 'catalog-hub',
                    'html' => '<script>alert("unsafe")</script>',
                ],
            ],
        ]);

        $this->assertNotNull(app(ExternalWidgetRenderer::class)->resolve($site, $projection, false));

        $html = $this->render($site, $projection, new Collection);

        $this->assertStringContainsString('data-external-price-widget', $html);
        $this->assertStringContainsString('https://widgets.trusted.test/offers', $html);
        $this->assertStringContainsString('product_id='.$projection->central_product_id, $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_it_does_not_render_the_widget_when_normalized_offers_are_available_in_auto_mode(): void
    {
        Config::set('pricing.external_widgets.providers.trusted_test.base_url', 'https://widgets.trusted.test/offers');
        [$site, $projection] = $this->scenario([
            'pricing' => [
                'provider_mode' => 'auto',
                'external_widget' => ['enabled' => true, 'provider' => 'trusted_test'],
            ],
        ]);
        $offer = MarketOffer::factory()->create();
        $offer->load('merchant.logoMediaAsset');

        $html = $this->render($site, $projection, new Collection([$offer]));

        $this->assertStringNotContainsString('data-external-price-widget', $html);
        $this->assertStringContainsString('data-offer-table', $html);
    }

    public function test_widget_mode_can_force_the_controlled_fallback(): void
    {
        Config::set('pricing.external_widgets.providers.trusted_test.base_url', 'https://widgets.trusted.test/offers');
        [$site, $projection] = $this->scenario([
            'pricing' => [
                'provider_mode' => 'widget',
                'external_widget' => ['enabled' => true, 'provider' => 'trusted_test'],
            ],
        ]);
        $offer = MarketOffer::factory()->create();

        $html = $this->render($site, $projection, new Collection([$offer]));

        $this->assertStringContainsString('data-external-price-widget', $html);
        $this->assertStringNotContainsString('data-offer-table', $html);
    }

    /** @param array<string, mixed> $settings */
    private function scenario(array $settings): array
    {
        $site = Site::factory()->create(['settings_json' => $settings]);
        $product = CentralProduct::factory()->create();
        $projection = SiteProductProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en',
            'central_product_id' => $product->id,
            'slug' => 'widget-product',
            'title' => 'Widget Product',
            'payload_json' => [],
            'media_json' => [],
        ]);

        return [$site, $projection];
    }

    /** @param Collection<int, MarketOffer> $offers */
    private function render(Site $site, SiteProductProjection $projection, Collection $offers): string
    {
        return Blade::render(
            '<x-public.offers-block :site="$site" :product-projection="$projection" :offers="$offers" />',
            compact('site', 'projection', 'offers'),
        );
    }
}
