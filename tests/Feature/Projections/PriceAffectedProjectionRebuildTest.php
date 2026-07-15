<?php

namespace Tests\Feature\Projections;

use App\Events\MarketOfferUpdated;
use App\Jobs\Projections\RebuildPriceAffectedProjectionJob;
use App\Listeners\RebuildPriceAffectedProjections;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Models\SiteSearchDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class PriceAffectedProjectionRebuildTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_dispatches_rebuild_only_for_affected_sites(): void
    {
        Bus::fake();
        $product = CentralProduct::factory()->create();
        $source = PriceSource::factory()->active()->create();
        $merchant = MarketMerchant::factory()->create(['market_id' => $source->market_id]);
        $offer = MarketOffer::factory()->create([
            'market_id' => $source->market_id,
            'market_merchant_id' => $merchant->id,
            'central_product_id' => $product->id,
            'price_source_id' => $source->id,
        ]);
        $affected = Site::factory()->create(['market_id' => $source->market_id]);
        $disabled = Site::factory()->create(['market_id' => $source->market_id]);
        $withoutProduct = Site::factory()->create(['market_id' => $source->market_id]);
        foreach ([$affected, $disabled] as $site) {
            SiteProduct::query()->create([
                'site_id' => $site->id,
                'central_product_id' => $product->id,
                'visibility' => 'visible',
            ]);
        }
        $affected->priceSources()->attach($source, ['enabled' => true]);
        $disabled->priceSources()->attach($source, ['enabled' => false]);
        $withoutProduct->priceSources()->attach($source, ['enabled' => true]);

        app(RebuildPriceAffectedProjections::class)->handle(new MarketOfferUpdated($offer->id));

        Bus::assertDispatchedTimes(RebuildPriceAffectedProjectionJob::class, 1);
        Bus::assertDispatched(
            RebuildPriceAffectedProjectionJob::class,
            fn (RebuildPriceAffectedProjectionJob $job): bool => $job->siteId === $affected->id
                && $job->centralProductId === $product->id,
        );
        Bus::assertNotDispatched(
            RebuildPriceAffectedProjectionJob::class,
            fn (RebuildPriceAffectedProjectionJob $job): bool => in_array($job->siteId, [$disabled->id, $withoutProduct->id], true),
        );
    }

    public function test_job_rebuilds_only_the_affected_product_search_document(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en']);
        $product = CentralProduct::factory()->create();
        $otherProduct = CentralProduct::factory()->create();
        SiteProduct::query()->create([
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'visibility' => 'visible',
        ]);
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $site->priceSources()->attach($source, ['enabled' => true]);
        $merchant = MarketMerchant::factory()->create(['market_id' => $site->market_id]);
        $offer = MarketOffer::factory()->create([
            'market_id' => $site->market_id,
            'market_merchant_id' => $merchant->id,
            'central_product_id' => $product->id,
            'price_source_id' => $source->id,
            'currency' => $site->market->currency_code,
            'price' => '249.99',
        ]);

        app(RebuildPriceAffectedProjectionJob::class, [
            'siteId' => $site->id,
            'centralProductId' => $product->id,
        ])->handle();

        $document = SiteSearchDocument::query()
            ->where('site_id', $site->id)
            ->where('document_type', 'product')
            ->where('document_id', $product->id)
            ->sole();
        $this->assertSame('249.99', $document->min_price);
        $this->assertDatabaseMissing('site_search_documents', [
            'site_id' => $site->id,
            'document_type' => 'product',
            'document_id' => $otherProduct->id,
        ]);

        $offer->update(['price' => '199.99']);
        (new RebuildPriceAffectedProjectionJob($site->id, $product->id))->handle();

        $this->assertSame('199.99', $document->fresh()->min_price);
        $this->assertSame(1, SiteSearchDocument::query()->where('site_id', $site->id)->count());
    }
}
