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
}
