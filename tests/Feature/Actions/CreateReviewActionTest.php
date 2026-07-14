<?php

namespace Tests\Feature\Actions;

use App\Actions\Reviews\CreateReviewAction;
use App\Domains\Projections\Enums\ProjectionStatus;
use App\Enums\ReviewStatus;
use App\Exceptions\Reviews\CannotCreateReviewException;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteFeature;
use App\Models\SiteProductProjection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateReviewActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_pending_review_for_an_enabled_visible_product(): void
    {
        [$site, $product] = $this->reviewContext();

        $review = app(CreateReviewAction::class)->handle(
            site: $site,
            product: $product,
            authorName: 'Ivan',
            authorEmail: 'ivan@example.com',
            rating: 5,
            pros: 'Fast and quiet.',
            cons: 'Expensive.',
            comment: null,
            locale: 'en-US',
        );

        $this->assertSame(ReviewStatus::Pending, $review->status);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'pros' => 'Fast and quiet.',
            'cons' => 'Expensive.',
            'status' => ReviewStatus::Pending->value,
        ]);
    }

    public function test_it_requires_at_least_one_review_text_field(): void
    {
        [$site, $product] = $this->reviewContext();

        try {
            app(CreateReviewAction::class)->handle(
                $site,
                $product,
                'Ivan',
                null,
                4,
                null,
                null,
                null,
                'en-US',
            );
            $this->fail('A review without text should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('pros', $exception->errors());
        }

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_it_rejects_creation_when_reviews_feature_is_disabled(): void
    {
        [$site, $product] = $this->reviewContext(featureEnabled: false);

        $this->expectException(CannotCreateReviewException::class);

        app(CreateReviewAction::class)->handle(
            $site,
            $product,
            'Ivan',
            null,
            4,
            null,
            null,
            'Useful review.',
            'en-US',
        );
    }

    public function test_it_rejects_a_product_without_an_active_site_projection(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        SiteFeature::query()->create([
            'site_id' => $site->id,
            'feature_key' => 'reviews',
            'is_enabled' => true,
        ]);

        try {
            app(CreateReviewAction::class)->handle(
                $site,
                $product,
                'Ivan',
                null,
                4,
                null,
                null,
                'Useful review.',
                'en-US',
            );
            $this->fail('An unavailable product should be rejected.');
        } catch (CannotCreateReviewException $exception) {
            $this->assertSame('This product is not available on the site.', $exception->getMessage());
        }

        $this->assertDatabaseCount('reviews', 0);
    }

    /** @return array{Site, CentralProduct} */
    private function reviewContext(bool $featureEnabled = true): array
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        SiteFeature::query()->create([
            'site_id' => $site->id,
            'feature_key' => 'reviews',
            'is_enabled' => $featureEnabled,
        ]);
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
