<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Public\Reviews\ReviewForm;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
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
}
