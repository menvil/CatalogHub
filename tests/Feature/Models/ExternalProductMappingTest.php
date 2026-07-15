<?php

namespace Tests\Feature\Models;

use App\Enums\ExternalProductMappingStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ExternalProductMapping;
use App\Models\PriceSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalProductMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_factory_creates_mapping_with_casts_and_source(): void
    {
        $mapping = ExternalProductMapping::factory()->pending()->create([
            'confidence' => 0.86,
            'metadata' => ['matcher' => 'sku'],
        ]);

        $this->assertSame(ExternalProductMappingStatus::Pending, $mapping->status);
        $this->assertSame('0.8600', $mapping->confidence);
        $this->assertSame(['matcher' => 'sku'], $mapping->metadata);
        $this->assertInstanceOf(PriceSource::class, $mapping->priceSource);
        $this->assertNull($mapping->centralProduct);
    }

    public function test_approved_factory_creates_audited_mapping(): void
    {
        $mapping = ExternalProductMapping::factory()->approved()->create();

        $this->assertSame(ExternalProductMappingStatus::Approved, $mapping->status);
        $this->assertInstanceOf(CentralProduct::class, $mapping->centralProduct);
        $this->assertInstanceOf(User::class, $mapping->approvedByUser);
        $this->assertNotNull($mapping->approved_at);
        $this->assertTrue(method_exists($mapping, 'marketOffers'));
    }
}
