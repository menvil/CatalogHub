<?php

namespace Tests\Unit\Models;

use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Models\Imports\NormalizationError;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Imports\RawProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RawProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_and_preserves_the_complete_raw_payload(): void
    {
        $payload = [
            'title' => 'Demo product',
            'specifications' => ['Power' => '100 W'],
            'unknown_field' => ['nested' => true],
        ];

        $rawProduct = RawProduct::factory()->create([
            'raw_payload_json' => $payload,
            'payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
        ])->fresh();

        $this->assertSame($payload, $rawProduct->raw_payload_json);
        $this->assertSame(hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)), $rawProduct->payload_hash);
        $this->assertIsInt($rawProduct->source_row_number);
    }

    public function test_exposes_pipeline_relationships(): void
    {
        $rawProduct = RawProduct::factory()->create();

        $this->assertInstanceOf(ImportBatch::class, $rawProduct->batch()->getRelated());
        $this->assertInstanceOf(ImportSource::class, $rawProduct->source()->getRelated());
        $this->assertInstanceOf(NormalizedProductDraft::class, $rawProduct->draft()->getRelated());
        $this->assertInstanceOf(NormalizationError::class, $rawProduct->errors()->getRelated());
    }

    public function test_factory_hash_uses_canonical_writer_encoding(): void
    {
        $rawProduct = RawProduct::factory()->make();
        $payload = $rawProduct->raw_payload_json;
        ksort($payload, SORT_STRING);
        $encoded = json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        $this->assertSame(hash('sha256', $encoded), $rawProduct->payload_hash);
    }
}
