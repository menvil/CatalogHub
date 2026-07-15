<?php

namespace Tests\Feature\Livewire;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Livewire\Public\Leads\LeadForm;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteFeature;
use App\Models\SiteProductProjection;
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

    public function test_lead_form_creates_lead_with_selected_type(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        SiteFeature::query()->create([
            'site_id' => $site->id,
            'feature_key' => 'leads',
            'is_enabled' => true,
        ]);
        SiteProductProjection::query()->create([
            'site_id' => $site->id,
            'locale' => $site->default_locale,
            'central_product_id' => $product->id,
            'slug' => 'test-product',
            'status' => ProjectionStatus::Active,
            'payload_json' => [],
        ]);

        Livewire::test(LeadForm::class, compact('site', 'product'))
            ->set('type', LeadType::BuyingAdvice->value)
            ->set('name', 'Ivan')
            ->set('email', 'ivan@example.com')
            ->set('message', 'Help me choose a monitor.')
            ->set('consentAccepted', true)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('leads', [
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'type' => LeadType::BuyingAdvice->value,
            'status' => LeadStatus::New->value,
        ]);
    }

    public function test_lead_form_requires_consent_before_creating_lead(): void
    {
        $site = Site::factory()->create();
        SiteFeature::query()->create([
            'site_id' => $site->id,
            'feature_key' => 'leads',
            'is_enabled' => true,
        ]);

        Livewire::test(LeadForm::class, ['site' => $site])
            ->assertSee('privacy policy')
            ->set('type', LeadType::Other->value)
            ->set('name', 'Ivan')
            ->set('email', 'ivan@example.com')
            ->set('message', 'Help me.')
            ->set('consentAccepted', false)
            ->call('submit')
            ->assertHasErrors(['consentAccepted' => 'accepted']);

        $this->assertDatabaseCount('leads', 0);
    }
}
