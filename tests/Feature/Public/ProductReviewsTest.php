<?php

namespace Tests\Feature\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Enums\SiteStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Review;
use App\Models\Site;
use App\Models\SiteFeature;
use App\Models\SiteProductProjection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EnablesSiteLocales;
use Tests\TestCase;

class ProductReviewsTest extends TestCase
{
    use EnablesSiteLocales;
    use RefreshDatabase;

    public function test_product_page_shows_only_approved_reviews_for_its_site_and_product(): void
    {
        [$site, $product] = $this->productPageContext();
        Review::factory()->approved()->create([
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'comment' => 'Excellent monitor.',
        ]);
        Review::factory()->pending()->create([
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'comment' => 'Pending text.',
        ]);
        Review::factory()->rejected()->create([
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'comment' => 'Rejected text.',
        ]);
        Review::factory()->spam()->create([
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'comment' => 'Spam text.',
        ]);

        $this->get('http://reviews.test/en-US/products/test-product')
            ->assertOk()
            ->assertSee('Customer reviews')
            ->assertSee('Excellent monitor.')
            ->assertSee('Leave a review')
            ->assertDontSee('Pending text.')
            ->assertDontSee('Rejected text.')
            ->assertDontSee('Spam text.');
    }

    public function test_product_page_shows_reviews_empty_state_when_feature_is_enabled(): void
    {
        $this->productPageContext();

        $this->get('http://reviews.test/en-US/products/test-product')
            ->assertOk()
            ->assertSee('No reviews yet')
            ->assertSee('Leave a review');
    }

    public function test_product_page_hides_reviews_and_form_when_feature_is_disabled(): void
    {
        $this->productPageContext(featureEnabled: false);

        $this->get('http://reviews.test/en-US/products/test-product')
            ->assertOk()
            ->assertDontSee('Customer reviews')
            ->assertDontSee('Leave a review');
    }

    /** @return array{Site, CentralProduct} */
    private function productPageContext(bool $featureEnabled = true): array
    {
        $site = Site::factory()->create([
            'domain' => 'reviews.test',
            'default_locale' => 'en-US',
            'status' => SiteStatus::Active,
        ]);
        $this->enableLocale($site, 'en-US');
        SiteFeature::query()->create([
            'site_id' => $site->id,
            'feature_key' => 'reviews',
            'is_enabled' => $featureEnabled,
        ]);
        $product = CentralProduct::factory()->create();
        SiteProductProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_product_id' => $product->id,
            'slug' => 'test-product',
            'title' => 'Test product',
            'status' => ProjectionStatus::Active,
            'payload_json' => [],
        ]);

        return [$site, $product];
    }
}
