<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\NormalizedProductDraftResource;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\DuplicateCandidate;
use App\Models\Imports\NormalizationError;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Imports\RawProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormalizedDraftReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_shows_normalized_data_raw_comparison_media_duplicates_and_errors(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $brand = CentralBrand::factory()->create(['name' => 'Acme']);
        $category = CentralCategory::factory()->create(['name' => 'Mixers']);
        $candidateProduct = CentralProduct::factory()
            ->for($brand, 'brand')
            ->for($category, 'category')
            ->create(['name' => 'Acme Mixer Candidate']);
        $rawProduct = RawProduct::factory()->create([
            'raw_title' => 'ACME mixer raw',
            'raw_payload_json' => ['Power source' => '500 W'],
        ]);
        $draft = NormalizedProductDraft::factory()
            ->for($rawProduct, 'rawProduct')
            ->create([
                'import_batch_id' => $rawProduct->import_batch_id,
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'title' => 'Acme Mixer 500',
                'attributes_json' => [['code' => 'power', 'value' => 500, 'unit' => 'watt']],
                'media_json' => [['source_url' => 'https://cdn.example.test/mixer.jpg', 'status' => 'downloaded']],
                'confidence' => '0.9200',
                'status' => 'pending_review',
            ]);
        DuplicateCandidate::query()->create([
            'import_batch_id' => $draft->import_batch_id,
            'normalized_product_draft_id' => $draft->id,
            'candidate_type' => 'central_product',
            'candidate_id' => $candidateProduct->id,
            'score' => '0.8800',
            'reason_json' => ['brand_match' => true],
            'status' => 'pending',
        ]);
        NormalizationError::query()->create([
            'import_batch_id' => $draft->import_batch_id,
            'raw_product_id' => $rawProduct->id,
            'normalized_product_draft_id' => $draft->id,
            'severity' => 'warning',
            'code' => 'unmapped_color',
            'message' => 'Color needs review',
        ]);

        $this->actingAs($admin)
            ->get(NormalizedProductDraftResource::getUrl('view', ['record' => $draft]))
            ->assertOk()
            ->assertSee('Acme Mixer 500')
            ->assertSee('Acme')
            ->assertSee('Mixers')
            ->assertSee('ACME mixer raw')
            ->assertSee('Power source')
            ->assertSee('power')
            ->assertSee('watt')
            ->assertSee('mixer.jpg')
            ->assertSee('brand_match')
            ->assertSee('unmapped_color');
    }
}
