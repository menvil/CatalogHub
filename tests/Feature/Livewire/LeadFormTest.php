<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Public\Leads\LeadForm;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_form_renders_with_optional_product_and_category_context(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $category = CentralCategory::factory()->create();

        Livewire::test(LeadForm::class, compact('site', 'product', 'category'))
            ->assertSet('site.id', $site->id)
            ->assertSet('productId', $product->id)
            ->assertSet('categoryId', $category->id)
            ->assertSee('Request help')
            ->assertSee('Your name')
            ->assertSee('How can we help?');
    }

    public function test_lead_form_renders_without_catalog_context(): void
    {
        $site = Site::factory()->create();

        Livewire::test(LeadForm::class, ['site' => $site])
            ->assertSet('productId', null)
            ->assertSet('categoryId', null)
            ->assertSee('Request help');
    }
}
