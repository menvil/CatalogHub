<?php

namespace Tests\Feature\Actions;

use App\Actions\Leads\CreateLeadAction;
use App\Domains\Projections\Enums\ProjectionStatus;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Exceptions\Leads\CannotCreateLeadException;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteFeature;
use App\Models\SiteProductProjection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateLeadActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_new_lead_with_product_context(): void
    {
        [$site, $product] = $this->leadContext();

        $lead = app(CreateLeadAction::class)->handle(
            site: $site,
            productId: $product->id,
            categoryId: null,
            type: LeadType::BuyingAdvice,
            name: 'Ivan',
            email: 'ivan@example.com',
            phone: null,
            city: 'Sofia',
            message: 'Help me choose a monitor.',
            consentAccepted: true,
            locale: 'en-US',
            source: 'product_page',
        );

        $this->assertSame(LeadStatus::New, $lead->status);
        $this->assertSame(LeadType::BuyingAdvice, $lead->type);
        $this->assertNotNull($lead->consent_accepted_at);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'type' => LeadType::BuyingAdvice->value,
            'status' => LeadStatus::New->value,
        ]);
    }

    public function test_it_requires_email_or_phone(): void
    {
        [$site] = $this->leadContext();

        try {
            app(CreateLeadAction::class)->handle(
                $site,
                null,
                null,
                LeadType::Other,
                'Ivan',
                null,
                null,
                null,
                'Please contact me.',
                true,
                'en-US',
                'site_form',
            );
            $this->fail('A lead without contact information should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('email', $exception->errors());
        }

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_it_rejects_creation_when_leads_feature_is_disabled(): void
    {
        [$site] = $this->leadContext(featureEnabled: false);

        $this->expectException(CannotCreateLeadException::class);

        app(CreateLeadAction::class)->handle(
            $site,
            null,
            null,
            LeadType::Other,
            'Ivan',
            'ivan@example.com',
            null,
            null,
            null,
            true,
            'en-US',
            'site_form',
        );
    }

    /** @return array{Site, CentralProduct} */
    private function leadContext(bool $featureEnabled = true): array
    {
        $site = Site::factory()->create();
        SiteFeature::query()->create([
            'site_id' => $site->id,
            'feature_key' => 'leads',
            'is_enabled' => $featureEnabled,
        ]);
        $product = CentralProduct::factory()->create();
        SiteProductProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_product_id' => $product->id,
            'slug' => 'test-product',
            'status' => ProjectionStatus::Active,
            'payload_json' => [],
        ]);

        return [$site, $product];
    }
}
