<?php

namespace Tests\Feature\Models;

use App\Enums\PriceSourceCredentialStatus;
use App\Models\PriceSource;
use App\Models\PriceSourceCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceSourceCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_credentials_with_source_and_casts(): void
    {
        $credentials = PriceSourceCredential::factory()->create([
            'status' => PriceSourceCredentialStatus::Active,
            'last_rotated_at' => now(),
        ]);

        $this->assertInstanceOf(PriceSource::class, $credentials->priceSource);
        $this->assertSame(PriceSourceCredentialStatus::Active, $credentials->status);
        $this->assertNotNull($credentials->last_rotated_at);
        $this->assertArrayNotHasKey('encrypted_credentials_json', $credentials->toArray());
    }
}
