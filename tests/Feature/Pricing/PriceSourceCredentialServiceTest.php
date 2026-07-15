<?php

namespace Tests\Feature\Pricing;

use App\Enums\PriceSourceCredentialStatus;
use App\Models\PriceSource;
use App\Services\Pricing\PriceSourceCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class PriceSourceCredentialServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_credentials_encrypted_at_rest_and_resolves_them(): void
    {
        $source = PriceSource::factory()->create();
        $service = app(PriceSourceCredentialService::class);
        $record = $service->store($source, [
            'api_key' => 'secret-key',
            'headers' => ['X-Partner' => 'private-header'],
        ]);

        $raw = DB::table('price_source_credentials')
            ->where('id', $record->id)
            ->value('encrypted_credentials_json');

        $this->assertIsString($raw);
        $this->assertStringNotContainsString('secret-key', $raw);
        $this->assertStringNotContainsString('private-header', $raw);
        $this->assertSame('secret-key', $service->resolve($source)['api_key']);
        $this->assertSame(PriceSourceCredentialStatus::Active, $record->status);
        $this->assertNotNull($record->last_rotated_at);
    }

    public function test_masks_every_credential_value_without_exposing_secrets(): void
    {
        $source = PriceSource::factory()->create();
        $service = app(PriceSourceCredentialService::class);
        $service->store($source, [
            'api_key' => 'secret-key',
            'nested' => ['token' => 'private-token'],
        ]);

        $masked = $service->mask($source);

        $this->assertSame('****-key', $masked['api_key']);
        $this->assertSame('****oken', $masked['nested']['token']);
        $this->assertStringNotContainsString('secret-key', json_encode($masked, JSON_THROW_ON_ERROR));
    }

    public function test_missing_credentials_are_reported_explicitly(): void
    {
        $this->expectException(RuntimeException::class);

        app(PriceSourceCredentialService::class)->resolve(PriceSource::factory()->create());
    }
}
