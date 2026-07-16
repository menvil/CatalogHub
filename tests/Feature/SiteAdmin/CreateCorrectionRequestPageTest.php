<?php

namespace Tests\Feature\SiteAdmin;

use App\Enums\ChangeRequestStatus;
use App\Filament\Resources\CorrectionRequestResource;
use App\Filament\Resources\CorrectionRequestResource\Pages\CreateCorrectionRequest;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateCorrectionRequestPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_admin_can_render_the_correction_request_form(): void
    {
        $site = Site::factory()->create();
        $admin = User::factory()->siteAdmin($site)->create();

        $this->actingAs($admin)
            ->get(CorrectionRequestResource::getUrl('create'))
            ->assertOk()
            ->assertSee('Create Correction Request')
            ->assertSee('Current central value');
    }

    public function test_site_admin_can_submit_a_correction_request_form(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create(['name' => 'Old title']);
        SiteProduct::factory()->for($site)->for($product, 'centralProduct')->create();
        $admin = User::factory()->siteAdmin($site)->create();

        Livewire::actingAs($admin)
            ->test(CreateCorrectionRequest::class)
            ->fillForm([
                'central_product_id' => $product->id,
                'field_path' => 'name',
                'proposed_value' => 'New title',
                'evidence_url' => 'https://manufacturer.example/product',
                'evidence_note' => 'Official product page.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('central_change_requests', [
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'field_path' => 'name',
            'status' => ChangeRequestStatus::Pending->value,
        ]);
        $this->assertSame('Old title', $product->fresh()->name);
    }

    public function test_site_admin_form_does_not_offer_products_from_another_site(): void
    {
        $site = Site::factory()->create();
        $ownProduct = CentralProduct::factory()->create(['name' => 'Own product']);
        $otherProduct = CentralProduct::factory()->create(['name' => 'Other product']);
        SiteProduct::factory()->for($site)->for($ownProduct, 'centralProduct')->create();
        SiteProduct::factory()->for(Site::factory())->for($otherProduct, 'centralProduct')->create();

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(CreateCorrectionRequest::class)
            ->assertFormFieldExists('central_product_id', function ($field) use ($otherProduct, $ownProduct): bool {
                $options = $field->getOptions();

                return array_key_exists($ownProduct->id, $options)
                    && ! array_key_exists($otherProduct->id, $options);
            });
    }
}
