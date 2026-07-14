<?php

namespace Tests\Unit\Models;

use App\Enums\ReviewStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Review;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_belongs_to_site_and_central_product_and_casts_status(): void
    {
        $review = Review::factory()->create([
            'status' => ReviewStatus::Pending,
            'metadata' => ['source' => 'product-page'],
        ]);

        $this->assertInstanceOf(Site::class, $review->site);
        $this->assertInstanceOf(CentralProduct::class, $review->centralProduct);
        $this->assertSame(ReviewStatus::Pending, $review->status);
        $this->assertSame(['source' => 'product-page'], $review->metadata);
    }

    public function test_review_scopes_filter_by_status_site_and_product(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $approved = Review::factory()->approved()->create([
            'site_id' => $site->id,
            'central_product_id' => $product->id,
        ]);
        Review::factory()->pending()->create([
            'site_id' => $site->id,
            'central_product_id' => $product->id,
        ]);
        Review::factory()->approved()->create();

        $reviews = Review::query()
            ->approved()
            ->forSite($site)
            ->forProduct($product)
            ->get();

        $this->assertCount(1, $reviews);
        $this->assertTrue($reviews->first()->is($approved));
    }
}
