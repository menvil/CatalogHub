<?php

namespace Tests\Feature\Livewire;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Enums\ReviewStatus;
use App\Livewire\Public\Reviews\ReviewForm;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteFeature;
use App\Models\SiteProductProjection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_form_component_renders_for_site_and_product(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();

        Livewire::test(ReviewForm::class, [
            'site' => $site,
            'product' => $product,
        ])
            ->assertSet('site.id', $site->id)
            ->assertSet('product.id', $product->id)
            ->assertSee('Leave a review')
            ->assertSee('Your name')
            ->assertSee('Your review');
    }

    public function test_rating_is_required_and_must_be_between_one_and_five(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();

        Livewire::test(ReviewForm::class, compact('site', 'product'))
            ->call('submit')
            ->assertHasErrors(['rating' => 'required'])
            ->set('rating', 6)
            ->call('submit')
            ->assertHasErrors(['rating' => 'max'])
            ->set('rating', 0)
            ->call('submit')
            ->assertHasErrors(['rating' => 'min'])
            ->set('rating', 5)
            ->call('submit')
            ->assertHasNoErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_form_submits_pros_and_cons_as_a_pending_review(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        SiteFeature::query()->create([
            'site_id' => $site->id,
            'feature_key' => 'reviews',
            'is_enabled' => true,
        ]);
        SiteProductProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_product_id' => $product->id,
            'slug' => 'test-product',
            'status' => ProjectionStatus::Active,
            'payload_json' => [],
        ]);

        Livewire::test(ReviewForm::class, compact('site', 'product'))
            ->set('authorName', 'Ivan')
            ->set('authorEmail', 'ivan@example.com')
            ->set('rating', 5)
            ->set('pros', 'Fast and quiet.')
            ->set('cons', 'Expensive.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('reviews', [
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'pros' => 'Fast and quiet.',
            'cons' => 'Expensive.',
            'status' => ReviewStatus::Pending->value,
        ]);
    }
}
