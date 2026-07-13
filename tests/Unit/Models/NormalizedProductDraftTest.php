<?php

namespace Tests\Unit\Models;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\DuplicateCandidate;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\NormalizationError;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Imports\RawProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormalizedProductDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_normalized_payload_attributes_media_and_review_metadata(): void
    {
        $draft = NormalizedProductDraft::factory()->create([
            'normalized_payload_json' => ['title' => 'Normalized title'],
            'attributes_json' => [['code' => 'power', 'value' => 100]],
            'media_json' => [['source_url' => 'https://example.test/image.jpg']],
            'confidence' => '0.9500',
            'approved_at' => now(),
        ])->fresh();

        $this->assertSame(['title' => 'Normalized title'], $draft->normalized_payload_json);
        $this->assertSame([['code' => 'power', 'value' => 100]], $draft->attributes_json);
        $this->assertSame([['source_url' => 'https://example.test/image.jpg']], $draft->media_json);
        $this->assertSame('0.9500', $draft->confidence);
        $this->assertNotNull($draft->approved_at);
    }

    public function test_exposes_review_pipeline_and_central_catalog_relationships(): void
    {
        $draft = NormalizedProductDraft::factory()->create();

        $this->assertInstanceOf(RawProduct::class, $draft->rawProduct()->getRelated());
        $this->assertInstanceOf(ImportBatch::class, $draft->importBatch()->getRelated());
        $this->assertInstanceOf(CentralBrand::class, $draft->brand()->getRelated());
        $this->assertInstanceOf(CentralCategory::class, $draft->category()->getRelated());
        $this->assertInstanceOf(CentralProduct::class, $draft->matchedCentralProduct()->getRelated());
        $this->assertInstanceOf(DuplicateCandidate::class, $draft->duplicateCandidates()->getRelated());
        $this->assertInstanceOf(NormalizationError::class, $draft->errors()->getRelated());
    }
}
